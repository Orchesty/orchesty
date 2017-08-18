<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFUserBundle\Handler;

use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\User\UserManager;

/**
 * Class UserHandler
 *
 * @package Hanaboso\PipesFramework\HbPFUserBundle\Handler
 */
class UserHandler
{

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * UserHandler constructor.
     *
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @param array $data
     *
     * @return User
     */
    public function login(array $data): User
    {
        return $this->userManager->login($data);
    }

    /**
     *
     */
    public function logout(): void
    {
        $this->userManager->logout();
    }

    /**
     * @param array $data
     */
    public function register(array $data): void
    {
        $this->userManager->register($data);
    }

    /**
     * @param string $id
     */
    public function activate(string $id): void
    {
        $this->userManager->activate($id);
    }

    /**
     * @param string $id
     * @param array  $data
     */
    public function setPassword(string $id, array $data): void
    {
        $this->userManager->setPassword($id, $data);
    }

    /**
     * @param array $data
     */
    public function resetPassword(array $data): void
    {
        $this->userManager->resetPassword($data);
    }

}