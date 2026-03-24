<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\User\Document\UserSettings;
use Hanaboso\PipesFramework\User\Manager\UserManager as UsersManager;
use Hanaboso\UserBundle\Document\TmpUser;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Enum\ResourceEnum;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\Token\TokenManager;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\UserBundle\Provider\ResourceProvider;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;
use Throwable;

/**
 * Class UserHandler
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Handler
 */
final class UserHandler
{

    private const string SETTINGS            = 'settings';
    private const int    USER_ALREADY_EXISTS = 1_202;

    /**
     * UserHandler constructor.
     *
     * @param UserManager      $userManager
     * @param UsersManager     $usersManager
     * @param DocumentManager  $dm
     * @param TokenManager     $tokenManager
     * @param ResourceProvider $resourceProvider
     */
    public function __construct(
        private UserManager $userManager,
        private UsersManager $usersManager,
        private DocumentManager $dm,
        private TokenManager $tokenManager,
        private ResourceProvider $resourceProvider,
    )
    {
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getAllUsers(GridRequestDto $dto): array
    {
        return $this->usersManager->getArrayOfUsers($dto);
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws UserManagerException
     */
    public function getUserDetail(string $id): array
    {
        return $this->getUserData($this->getUser($id), '');
    }

    /**
     * @param mixed[] $data
     * @param string  $id
     *
     * @return UserSettings
     * @throws MongoDBException
     * @throws PipesFrameworkException
     * @throws UserManagerException
     */
    public function saveSettings(array $data, string $id): UserSettings
    {
        ControllerUtils::checkParameters([self::SETTINGS], $data);

        $settings = $this->getSettings($id);
        if (!$settings) {
            $settings = (new UserSettings())
                ->setUserId($this->getUser($id)->getId());
        }

        $settings->setSettings($data[self::SETTINGS]);

        $this->dm->persist($settings);
        $this->dm->flush();

        return $settings;
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws DateTimeException
     * @throws PipesFrameworkException
     * @throws SecurityManagerException
     */
    public function login(array $data): array
    {
        ControllerUtils::checkParameters(['email', 'password'], $data);
        [$user, $jwt] = $this->userManager->login($data);

        return $this->getUserData($user, $jwt);
    }

    /**
     * @return mixed[]
     */
    public function hasUser(): array
    {
        return ['hasUser' => $this->usersManager->hasUser()];
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
        ControllerUtils::checkParameters(['email', 'password'], $data);

        return $this->usersManager->setupUser($data['email'], $data['password'])->toArray();
    }

    /**
     * @param string $email
     *
     * @return mixed[]
     * @throws UserManagerException
     */
    public function inviteUser(string $email): array
    {
        if ($this->dm->getRepository(User::class)->findOneBy(['email' => $email])) {
            throw new UserManagerException(
                sprintf('User with email [%s] already exists.', $email),
                self::USER_ALREADY_EXISTS,
            );
        }

        try {
            /** @var class-string<TmpUser> $tmpUserClass */
            $tmpUserClass = $this->resourceProvider->getResource(ResourceEnum::TMP_USER);
            /** @var TmpUser|null $tmpUser */
            $tmpUser = $this->dm->getRepository($tmpUserClass)->findOneBy(['email' => $email]);

            if (!$tmpUser) {
                $tmpUser = new $tmpUserClass();
                $tmpUser->setEmail($email);
                $this->dm->persist($tmpUser);
                $this->dm->flush();
            }

            $token = $this->tokenManager->create($tmpUser);

            return ['hash' => $token->getHash(), 'email' => $email];
        } catch (UserManagerException $e) {
            throw $e;
        } catch (Throwable $t) {
            throw new UserManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getAllInvitedUsers(GridRequestDto $dto): array
    {
        return $this->usersManager->getArrayOfInvitedUsers($dto);
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws UserManagerException
     */
    public function regenerateInvite(string $id): array
    {
        try {
            $tmpUser = $this->usersManager->getInvitedUser($id);
            $token   = $this->tokenManager->create($tmpUser);

            return ['hash' => $token->getHash(), 'email' => $tmpUser->getEmail()];
        } catch (UserManagerException $e) {
            throw $e;
        } catch (Throwable $t) {
            throw new UserManagerException($t->getMessage(), $t->getCode(), $t);
        }
    }

    /**
     * @param string $id
     *
     * @throws MongoDBException
     * @throws UserManagerException
     */
    public function deleteInvitedUser(string $id): void
    {
        $this->usersManager->deleteInvitedUser($id);
    }

    /*
     * ----------------------------------- HELPERS -----------------------------------
     */

    /**
     * @param User   $user
     * @param string $jwt
     *
     * @return mixed[]
     */
    private function getUserData(User $user, string $jwt): array
    {
        $settings = $this->getSettings($user->getId());
        if ($settings) {
            $settings = $settings->getSettings();
        }

        return array_merge($user->toArray(), [self::SETTINGS => $settings ?? []], ['token' => $jwt]);
    }

    /**
     * @param string $id
     *
     * @return UserSettings|null
     */
    private function getSettings(string $id): ?UserSettings
    {
        $userRepository = $this->dm->getRepository(UserSettings::class);
        /** @var UserSettings|null $userSettings */
        $userSettings = $userRepository->findOneBy(['userId' => $id]);

        if ($userSettings) {
            return $userSettings;
        }

        return NULL;
    }

    /**
     * @param string $id
     *
     * @return User
     * @throws UserManagerException
     */
    private function getUser(string $id): User
    {
        /** @var User|null $user */
        $user = $this->dm->getRepository(User::class)->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User with id [%s] not found.', $id),
                UserManagerException::USER_NOT_EXISTS,
            );
        }

        return $user;
    }

}
