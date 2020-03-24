<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\PipesFramework\User\Document\UserSettings;
use Hanaboso\PipesFramework\User\Manager\UserManager as UsersManager;
use Hanaboso\UserBundle\Document\User;
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
class UserHandler
{

    private const SETTINGS = 'settings';

    /**
     * @var UsersManager
     */
    private UsersManager $usersManager;

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * @var UserManager
     */
    private UserManager $userManager;

    /**
     * UserHandler constructor.
     *
     * @param UserManager     $userManager
     * @param UsersManager    $usersManager
     * @param DocumentManager $dm
     */
    public function __construct(UserManager $userManager, UsersManager $usersManager, DocumentManager $dm)
    {
        $this->userManager  = $userManager;
        $this->usersManager = $usersManager;
        $this->dm           = $dm;
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

        $settings = (new UserSettings())
            ->setSettings($data[self::SETTINGS])
            ->setUserId($this->getUser($id)->getId());

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

        $user     = $this->userManager->login($data);
        $settings = $this->getSettings($user->getId());
        $data     = array_merge($user->toArray(), [self::SETTINGS => $settings]);

        return $data;
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     */
    private function getSettings(string $id): array
    {
        /** @var ObjectRepository<UserSettings> $userRepository */
        $userRepository = $this->dm->getRepository(UserSettings::class);
        /** @var UserSettings|null $userSettings */
        $userSettings = $userRepository->findOneBy(['userId' => $id]);

        if ($userSettings) {
            return $userSettings->getSettings();
        }

        return [];
    }

    /**
     * @param string $id
     *
     * @return User
     * @throws UserManagerException
     */
    private function getUser(string $id): User
    {
        $user = $this->dm->getRepository(User::class)->findOneBy(['id' => $id]);

        if (!$user) {
            throw new UserManagerException(
                sprintf('User with id [%s] not found.', $id),
                UserManagerException::USER_NOT_EXISTS
            );
        }

        return $user;
    }

}
