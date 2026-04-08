<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\AclBundle\Document\Group;
use Hanaboso\AclBundle\Manager\GroupManager;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\PipesFramework\User\Manager\UserManager as UsersManager;
use Hanaboso\PipesFrameworkEnterprise\Acl\PermissionPresets;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Token\TokenManager;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Throwable;

/**
 * Class EnterpriseUserHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class EnterpriseUserHandler extends UserHandler
{

    private const int USER_ALREADY_EXISTS = 1_202;

    /**
     * EnterpriseUserHandler constructor.
     *
     * @param UserManager                    $userManager
     * @param UsersManager                   $usersManager
     * @param DocumentManager                $dm
     * @param TokenManager                   $tokenManager
     * @param ResourceProvider               $resourceProvider
     * @param CloudMemberSyncService         $cloudMemberSyncService
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     * @param GroupManager                   $groupManager
     * @param SystemTopologyService          $systemTopologyService
     */
    public function __construct(
        UserManager $userManager,
        UsersManager $usersManager,
        DocumentManager $dm,
        private readonly TokenManager $tokenManager,
        ResourceProvider $resourceProvider,
        private readonly CloudMemberSyncService $cloudMemberSyncService,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
        private readonly GroupManager $groupManager,
        private readonly SystemTopologyService $systemTopologyService,
    )
    {
        parent::__construct($userManager, $usersManager, $dm, $tokenManager, $resourceProvider);
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws PipesFrameworkException
     */
    public function setupUser(array $data): array
    {
        $result = parent::setupUser($data);

        $this->ensurePresetGroups();

        /** @var User|null $user */
        $user = $this->dm->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if ($user instanceof User) {
            $this->groupManager->addUserIntoGroup($user, groupName: PermissionPresets::SUPER_ADMIN);
        }

        return $result;
    }

    /**
     * Creates or un-deletes a user directly (no invite link).
     * Used for cloud account users who already have Auth0 credentials.
     *
     * @param string      $email
     * @param string|null $name
     *
     * @return mixed[]
     * @throws UserManagerException
     */
    public function addUserFromAccount(string $email, ?string $name = NULL): array
    {
        /** @var User|null $existing */
        $existing = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing) {
            if (!$existing->isDeleted()) {
                throw new UserManagerException(
                    sprintf('User with email [%s] already exists.', $email),
                    self::USER_ALREADY_EXISTS,
                );
            }

            $existing->setDeleted(FALSE);
            $this->dm->flush();
            $this->cloudMemberSyncService->syncMemberAdd($email, $name);
            $this->systemTopologyService->sendRestoreAccessEmail($email);

            return ['email' => $email, 'added' => TRUE];
        }

        $user = new User();
        $user->setEmail($email);

        $hasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $user->setPassword($hasher->hash(bin2hex(random_bytes(32))));

        $this->dm->persist($user);
        $this->dm->flush();

        try {
            $this->groupManager->addUserIntoGroup($user, groupName: 'user');
        } catch (Throwable) {
        }

        $this->cloudMemberSyncService->syncMemberAdd($email, $name);

        return ['email' => $email, 'added' => TRUE];
    }

    /**
     * Generates a password reset token and sends a forgot-password email
     * via the system topology. Always returns the same message to prevent
     * email enumeration.
     *
     * @param string $email
     *
     * @return mixed[]
     */
    public function forgotPassword(string $email): array
    {
        /** @var User|null $user */
        $user = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || $user->isDeleted()) {
            return ['message' => 'If the email exists, reset instructions will be sent.'];
        }

        try {
            $token = $this->tokenManager->create($user);
            $this->systemTopologyService->sendForgotPasswordEmail($email, $token->getHash());
        } catch (Throwable) {
        }

        return ['message' => 'If the email exists, reset instructions will be sent.'];
    }

    /**
     * Handles invite with cloud-aware logic:
     * - soft-deleted users are re-activated
     * - existing cloud account users are added directly
     * - truly new users get a regular invite link
     *
     * When $groupIds are provided, the groups are either assigned immediately
     * (for direct-add / restore paths) or stored as pending for assignment
     * on activation (for the invite-link path).
     *
     * @param string   $email
     * @param string[] $groupIds
     *
     * @return mixed[]
     * @throws UserManagerException
     */
    public function inviteUser(string $email, array $groupIds = []): array
    {
        /** @var User|null $existing */
        $existing = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing) {
            if ($existing->isDeleted()) {
                $existing->setDeleted(FALSE);
                $this->dm->flush();
                $this->assignGroupsToUser($existing, $groupIds);
                $this->cloudMemberSyncService->syncMemberAdd($email);
                $this->systemTopologyService->sendRestoreAccessEmail($email);

                return ['email' => $email, 'added' => TRUE];
            }

            throw new UserManagerException(
                sprintf('User with email [%s] already exists.', $email),
                self::USER_ALREADY_EXISTS,
            );
        }

        if ($this->cloudMemberSyncService->isEnabled()) {
            $cloudUsers = $this->cloudMemberSyncService->searchAccountUsers($email);
            foreach ($cloudUsers as $cu) {
                if (strcasecmp($cu['email'] ?? '', $email) === 0) {
                    $result = $this->addUserFromAccount($email, $cu['name'] ?? NULL);

                    /** @var User|null $user */
                    $user = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);
                    if ($user) {
                        $this->assignGroupsToUser($user, $groupIds);
                    }

                    return $result;
                }
            }

            $localResult = parent::inviteUser($email);
            $this->storePendingGroups($email, $groupIds);

            $cloudResult = $this->cloudMemberSyncService->createCloudInvite($email);
            if ($cloudResult !== NULL) {
                $result = array_merge(['email' => $email], $cloudResult);
                $this->systemTopologyService->sendInviteEmail($email, $result['hash'] ?? '');

                return $result;
            }

            $this->systemTopologyService->sendInviteEmail($email, $localResult['hash'] ?? '');

            return $localResult;
        }

        $result = parent::inviteUser($email);
        $this->storePendingGroups($email, $groupIds);
        $this->systemTopologyService->sendInviteEmail($email, $result['hash'] ?? '');

        return $result;
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws UserManagerException
     */
    public function regenerateInvite(string $id): array
    {
        $result = parent::regenerateInvite($id);
        $this->systemTopologyService->sendInviteEmail($result['email'], $result['hash']);

        return $result;
    }

    /**
     * @param string $id
     *
     * @throws MongoDBException
     * @throws UserManagerException
     */
    public function deleteInvitedUser(string $id): void
    {
        $tmpUser = $this->usersManager->getInvitedUser($id);
        $email   = $tmpUser->getEmail();

        parent::deleteInvitedUser($id);

        $this->cloudMemberSyncService->syncMemberRemove($email);
    }

    /**
     * Soft-deletes a user, syncs removal to cloud, and bypasses the vendor
     * UserManager::delete() which relies on legacy JWT verification.
     *
     * @param string $id
     *
     * @return mixed[]
     * @throws UserManagerException
     */
    public function deleteUser(string $id): array
    {
        /** @var User|null $user */
        $user = $this->dm->getRepository(User::class)->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User with id [%s] not found.', $id),
                UserManagerException::USER_NOT_EXISTS,
            );
        }

        $this->cloudMemberSyncService->syncMemberRemove($user->getEmail());

        $user->setDeleted(TRUE);
        $this->dm->flush();

        return $user->toArray();
    }

    /**
     * Creates all preset groups in the database if they don't exist yet.
     */
    private function ensurePresetGroups(): void
    {
        $repo          = $this->dm->getRepository(Group::class);
        $existingNames = [];

        /** @var Group $g */
        foreach ($repo->findAll() as $g) {
            $existingNames[] = $g->getName();
        }

        foreach (PermissionPresets::all() as $name => $preset) {
            if (in_array($name, $existingNames, TRUE)) {
                continue;
            }

            $group = new Group(NULL);
            $group->setName($name);
            $group->setLevel($preset['level']);
            $this->dm->persist($group);
        }

        $this->dm->flush();
    }

    /**
     * @param User     $user
     * @param string[] $groupIds
     */
    private function assignGroupsToUser(User $user, array $groupIds): void
    {
        if ($groupIds === []) {
            return;
        }

        foreach ($groupIds as $groupId) {
            try {
                /** @var Group|null $group */
                $group = $this->dm->getRepository(Group::class)->find($groupId);
                $group?->addUser($user);
            } catch (Throwable) {
            }
        }

        $this->dm->flush();
    }

    /**
     * @param string   $email
     * @param string[] $groupIds
     */
    private function storePendingGroups(string $email, array $groupIds): void
    {
        if ($groupIds === []) {
            return;
        }

        $db = $this->dm->getDocumentDatabase(User::class);
        $db->selectCollection('PendingGroupAssignment')->updateOne(
            ['email' => $email],
            ['$set' => ['email' => $email, 'groupIds' => array_values($groupIds)]],
            ['upsert' => TRUE],
        );
    }

}
