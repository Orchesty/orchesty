<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\AclBundle\Document\Group;
use Hanaboso\AclBundle\Manager\GroupManager;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\PipesFramework\User\Manager\UserManager as UsersManager;
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

        /** @var User|null $user */
        $user = $this->dm->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if ($user instanceof User) {
            $group = new Group(NULL);
            $group->setName('superadmin');
            $group->setLevel(0);
            $group->addUser($user);

            $this->dm->persist($group);
            $this->dm->flush();
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
     * @param string $email
     *
     * @return mixed[]
     * @throws UserManagerException
     */
    public function inviteUser(string $email): array
    {
        /** @var User|null $existing */
        $existing = $this->dm->getRepository(User::class)->findOneBy(['email' => $email]);

        if ($existing) {
            if ($existing->isDeleted()) {
                $existing->setDeleted(FALSE);
                $this->dm->flush();
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
                    return $this->addUserFromAccount($email, $cu['name'] ?? NULL);
                }
            }

            $localResult = parent::inviteUser($email);

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

}
