<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Model\User\Event;

use Hanaboso\PipesFramework\User\Document\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class UserEvent
 *
 * @package Hanaboso\PipesFramework\User\Model\User\Event
 */
class UserEvent extends Event
{

    public const USER_LOGIN          = 'user.login';
    public const USER_LOGOUT         = 'user.logout';
    public const USER_REGISTER       = 'user.register';
    public const USER_ACTIVATE       = 'user.activate';
    public const USER_RESET_PASSWORD = 'user.reset.password';

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * UserEvent constructor.
     *
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @return UserInterface
     */
    public function getUser(): UserInterface
    {
        return $this->user;
    }

}