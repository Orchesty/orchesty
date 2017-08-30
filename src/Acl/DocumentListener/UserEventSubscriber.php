<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\DocumentListener;

use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Factory\RuleFactory;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\User\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class DocumentListener
 *
 * @package Hanaboso\PipesFramework\Acl\EntityListener
 */
class UserEventSubscriber implements EventSubscriberInterface
{

    /**
     * @var RuleFactory
     */
    private $factory;

    /**
     * DocumentListener constructor.
     *
     * @param RuleFactory $factory
     */
    function __construct(RuleFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param UserEvent $event
     */
    public function createGroup(UserEvent $event): void
    {
        /** @var User $user */
        $user  = $event->getUser();
        $group = new Group();
        $group
            ->setName($user->getEmail())
            ->addUser($user)
            ->setOwner($user);

        $this->factory->setDefaultRules($group);
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::USER_ACTIVATE => 'createGroup',
        ];
    }

}