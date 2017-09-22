<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Manager;

use Hanaboso\PipesFramework\Acl\Enum\ActionEnum;
use Hanaboso\PipesFramework\User\Enum\ResourceEnum;
use Hanaboso\PipesFramework\User\Model\User\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class UserManager
 *
 * @package Hanaboso\PipesFramework\Acl\Manager
 */
class UserManager implements EventSubscriberInterface
{

    /**
     * @var AccessManager
     */
    private $accessManager;

    /**
     * UserManager constructor.
     *
     * @param AccessManager $accessManager
     */
    public function __construct(AccessManager $accessManager)
    {
        $this->accessManager = $accessManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::USER_DELETE_BEFORE => 'checkPermission',
        ];
    }

    /**
     * @param UserEvent $userEvent
     */
    public function checkPermission(UserEvent $userEvent): void
    {
        $this->accessManager->isAllowed(
            ActionEnum::DELETE,
            ResourceEnum::USER,
            $userEvent->getLoggedUser(),
            $userEvent->getUser()
        );
    }

}