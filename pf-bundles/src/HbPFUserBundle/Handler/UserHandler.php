<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\User\Document\UserSettings;
use Hanaboso\PipesFramework\User\Manager\UserManager as UsersManager;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Entity\UserInterface;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\User\UserManager;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\System\ControllerUtils;

/**
 * Class UserHandler
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Handler
 */
final class UserHandler
{

    private const SETTINGS = 'settings';

    /**
     * UserHandler constructor.
     *
     * @param UserManager     $userManager
     * @param UsersManager    $usersManager
     * @param DocumentManager $dm
     */
    public function __construct(
        private UserManager $userManager,
        private UsersManager $usersManager,
        private DocumentManager $dm,
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
     * ----------------------------------- HELPERS -----------------------------------
     */

    /**
     * @param UserInterface $user
     * @param string        $jwt
     *
     * @return mixed[]
     */
    private function getUserData(UserInterface $user, string $jwt): array
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
