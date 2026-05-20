<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\AclBundle\Document\Group;
use Hanaboso\AclBundle\Manager\GroupManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFrameworkEnterprise\Acl\PermissionPresets;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\Token;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\Utils\System\ControllerUtils;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Throwable;

/**
 * Class CloudUserSyncHandler
 *
 * Cloud → worker user sync is intentionally narrow: only cloud OWNER and
 * ADMIN roles are auto-provisioned with the worker SUPER_ADMIN preset.
 *   OWNER + ADMIN  -> super_admin
 *   DEVELOPER      -> not auto-synced (invited from inside the worker via
 *                     the AddUserModal flow that pulls from cloud
 *                     `accountMember` on demand)
 *   BILLING        -> not propagated (cloud-only billing access)
 *
 * Cloud-side filtering (`getInstanceUsersForCallback` and
 * `bulkAddInstanceMembers`) is the primary defence — only OWNER/ADMIN ever
 * reach the worker. The role check below is a defensive belt-and-braces
 * net so a future cloud-side bug can't accidentally widen the surface.
 *
 * Group assignment is idempotent: provisionSingleUser / syncUsers reapply
 * the preset to existing users so previously broken provisioning self-heals
 * on the next webhook or session-handoff. Both entry points proactively
 * materialise the preset Group documents via EnterpriseGroupHandler before
 * any addUserIntoGroup() call, so freshly provisioned instances that have
 * never seen the local setupUser flow don't fall over with
 * "Group [super_admin] was not found!".
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class CloudUserSyncHandler
{

    private const array ROLE_MAP = [
        'ADMIN' => PermissionPresets::SUPER_ADMIN,
        'OWNER' => PermissionPresets::SUPER_ADMIN,
    ];

    private readonly LoggerInterface $logger;

    /**
     * CloudUserSyncHandler constructor.
     *
     * @param CurlManager                    $curlManager
     * @param DocumentManager                $dm
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     * @param GroupManager                   $groupManager
     * @param EnterpriseGroupHandler         $groupHandler
     * @param string                         $cloudUrl
     * @param LoggerInterface|null           $logger
     */
    public function __construct(
        private readonly CurlManager $curlManager,
        private readonly DocumentManager $dm,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly GroupManager $groupManager,
        private readonly EnterpriseGroupHandler $groupHandler,
        private readonly string $cloudUrl,
        ?LoggerInterface $logger = NULL,
    )
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function syncUsers(array $data): array
    {
        ControllerUtils::checkParameters(['token'], $data);

        $this->groupHandler->ensurePresetGroups();

        $users    = $this->fetchUsersFromCloud($data['token']);
        $created  = 0;
        $skipped  = 0;
        $repaired = 0;
        $failed   = 0;

        foreach ($users as $cloudUser) {
            $email = $cloudUser['email'] ?? NULL;
            if (!$email) {
                $skipped++;

                continue;
            }

            $cloudRole = $cloudUser['role'] ?? NULL;
            if (!is_string($cloudRole) || !isset(self::ROLE_MAP[$cloudRole])) {
                $skipped++;

                continue;
            }

            try {
                $existing = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $reassigned = $this->ensureGroup($existing, $cloudRole);
                    $this->removeTmpUser($email);
                    if ($reassigned) {
                        $repaired++;
                    } else {
                        $skipped++;
                    }

                    continue;
                }

                $this->provisionSingleUser((string) $email, $cloudRole);
                $created++;
            } catch (Throwable $e) {
                $failed++;
                $this->logger->error(
                    sprintf(
                        '[CloudUserSyncHandler] Failed to sync %s (role=%s): %s',
                        (string) $email,
                        $cloudRole,
                        $e->getMessage(),
                    ),
                    ['exception' => $e],
                );
            }
        }

        return [
            'created'  => $created,
            'failed'   => $failed,
            'repaired' => $repaired,
            'skipped'  => $skipped,
            'total'    => count($users),
        ];
    }

    /**
     * Provision a single user from a cloud handoff payload.
     *
     * Idempotent — when a user with the same e-mail already exists, the
     * existing row is returned and its preset group is reapplied (self-heals
     * any earlier broken provisioning). Used by both bulk syncUsers() and the
     * on-the-fly inline provisioning during cloud session handoff
     * (CloudWebhookController).
     *
     * Only OWNER and ADMIN cloud roles trigger auto-creation. For any other
     * role (DEVELOPER, BILLING, NULL, …) we throw — those users must be
     * invited from inside the worker via the AddUserModal flow. The cloud
     * gates session-handoff on instanceMember and only adds OWNER/ADMIN to
     * instanceMember, so the throw path is the "shouldn't happen" branch.
     *
     * @param string      $email     e-mail address from the cloud
     * @param string|NULL $cloudRole optional cloud role (OWNER or ADMIN)
     *
     * @return User
     * @throws MongoDBException
     * @throws RuntimeException when $cloudRole is not OWNER or ADMIN
     */
    public function provisionSingleUser(string $email, ?string $cloudRole = NULL): User
    {
        if (!is_string($cloudRole) || !isset(self::ROLE_MAP[$cloudRole])) {
            throw new RuntimeException(
                sprintf(
                    'Cloud role %s is not auto-provisioned in the worker (only OWNER/ADMIN); invite this user from inside the instance.',
                    $cloudRole ?? 'NULL',
                ),
            );
        }

        $this->groupHandler->ensurePresetGroups();

        $existing = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $this->ensureGroup($existing, $cloudRole);
            $this->removeTmpUser($email);

            return $existing;
        }

        $user = new User();
        $user->setEmail($email);

        $hasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $user->setPassword($hasher->hash(bin2hex(random_bytes(32))));

        $this->dm->persist($user);
        $this->dm->flush();

        $this->ensureGroup($user, $cloudRole);

        $this->removeTmpUser($email);

        return $user;
    }

    /**
     * Consume an instance-side invite token that was forwarded inside a
     * cloud handoff payload (invite-flow A).
     *
     * Flow recap:
     *   1. Admin invites email X in pipes  -> InvitationManager creates
     *      TmpUser(email=X) + Token(hash=$t).
     *   2. The cloud /accept-invite/:t page validates $t against this
     *      instance, sends the user through cloud SSO, then calls the
     *      session-handoff endpoint with `linkInviteToken=$t`.
     *   3. Cloud mints a handoff JWT containing `linkInviteToken=$t` and
     *      redirects to the instance.
     *   4. CloudWebhookController validates the JWT and either finds an
     *      existing User or provisions one inline. Then it calls THIS
     *      method to discard the now-redundant invite token/TmpUser so
     *      the same link can't be reused and the admin sees the invite
     *      as "accepted" in the UI.
     *
     * Best-effort: a missing/expired token is a silent no-op. The user is
     * already authenticated via the handoff JWT; cleaning up the invite
     * record is a courtesy, not a security boundary.
     *
     * @param User   $user        the just-authenticated user (kept for signature symmetry / future audit)
     * @param string $inviteToken raw invite-token hash from the cloud handoff payload
     */
    public function acceptInviteForUser(User $user, string $inviteToken): void
    {
        $user;

        try {
            /** @var Token|null $token */
            $token = $this->dm->getRepository(Token::class)->findOneBy(['hash' => $inviteToken]);
            if (!$token) {
                return;
            }

            // Use the typed accessors directly so we don't trip on Token::
            // getUserOrTmpUser() which throws LogicException when neither
            // relation is set (legitimate edge case after a stale token
            // was partially cleaned up).
            $tmp      = $token->getTmpUser();
            $existing = $token->getUser();
            if ($tmp instanceof TmpUser) {
                $this->dm->remove($tmp);
            } elseif ($existing instanceof User) {
                $existing->setToken(NULL);
            }

            $this->dm->remove($token);
            $this->dm->flush();
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('[CloudUserSyncHandler] acceptInviteForUser failed: %s', $e->getMessage()),
                ['exception' => $e],
            );
        }
    }

    /**
     * Make sure $user belongs to the preset group derived from $cloudRole.
     * No-op if the user is already a member. Logs and rethrows on failure.
     *
     * Throws when $cloudRole is unmapped — callers must filter to OWNER/ADMIN
     * before invoking this method (see ROLE_MAP).
     *
     * @param User        $user
     * @param string|NULL $cloudRole
     *
     * @return bool TRUE when the membership was added, FALSE if already present.
     */
    private function ensureGroup(User $user, ?string $cloudRole): bool
    {
        if (!is_string($cloudRole) || !isset(self::ROLE_MAP[$cloudRole])) {
            throw new RuntimeException(
                sprintf(
                    'Cannot assign worker group for cloud role %s — only OWNER/ADMIN are auto-mapped.',
                    $cloudRole ?? 'NULL',
                ),
            );
        }

        $groupName = self::ROLE_MAP[$cloudRole];

        try {
            if ($this->isUserInGroup($user, $groupName)) {
                return FALSE;
            }

            $this->groupManager->addUserIntoGroup($user, groupName: $groupName);

            return TRUE;
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf(
                    '[CloudUserSyncHandler] Failed to add %s to group %s: %s',
                    $user->getEmail(),
                    $groupName,
                    $e->getMessage(),
                ),
                ['exception' => $e],
            );

            throw $e;
        }
    }

    /**
     * @param User   $user
     * @param string $groupName
     *
     * @return bool
     */
    private function isUserInGroup(User $user, string $groupName): bool
    {
        /** @var Group|null $group */
        $group = $this->dm->getRepository(Group::class)->findOneBy(['name' => $groupName]);
        if (!$group) {
            return FALSE;
        }

        foreach ($group->getUsers() as $member) {
            if ($member->getId() === $user->getId()) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @param string $email
     */
    private function removeTmpUser(string $email): void
    {
        $tmpUser = $this->dm->getRepository(TmpUser::class)->findOneBy(['email' => $email]);
        if ($tmpUser) {
            $this->dm->remove($tmpUser);
            $this->dm->flush();
        }
    }

    /**
     * @param string $token
     *
     * @return mixed[]
     */
    private function fetchUsersFromCloud(string $token): array
    {
        $url = sprintf('%s/api/public/instance-users?token=%s', rtrim($this->cloudUrl, '/'), $token);

        $dto = new RequestDto(
            new Uri($url),
            CurlManager::METHOD_GET,
            new ProcessDto(),
        );

        $response = $this->curlManager->send($dto);

        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(
                sprintf('Cloud API returned status %d', $response->getStatusCode()),
            );
        }

        $body = $response->getJsonBody();

        return $body['users'] ?? [];
    }

}
