<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Subscriber;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\AclBundle\Document\Group;
use Hanaboso\AclBundle\Manager\GroupManager;
use Hanaboso\PipesFrameworkEnterprise\Acl\PermissionPresets;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\User\Event\ActivateUserEvent;
use Hanaboso\UserBundle\Model\User\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;

/**
 * Class UserActivateSubscriber
 *
 * Assigns pending groups (stored during invite) to the newly activated user.
 * Falls back to the default 'user' group when no pending assignment exists.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Subscriber
 */
final class UserActivateSubscriber implements EventSubscriberInterface
{

    /**
     * UserActivateSubscriber constructor.
     *
     * @param DocumentManager $dm
     * @param GroupManager    $groupManager
     */
    public function __construct(private readonly DocumentManager $dm, private readonly GroupManager $groupManager)
    {
    }

    /**
     * @param ActivateUserEvent $event
     */
    public function onUserActivate(ActivateUserEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $db         = $this->dm->getDocumentDatabase(User::class);
        $collection = $db->selectCollection('PendingGroupAssignment');
        $pending    = $collection->findOneAndDelete(['email' => $user->getEmail()]);

        if (is_array($pending) && isset($pending['groupIds']) && $pending['groupIds'] !== []) {
            foreach ($pending['groupIds'] as $groupId) {
                try {
                    /** @var Group|null $group */
                    $group = $this->dm->getRepository(Group::class)->find((string) $groupId);
                    $group?->addUser($user);
                } catch (Throwable) {
                }
            }
        } else {
            try {
                $this->groupManager->addUserIntoGroup($user, groupName: PermissionPresets::MONITORING);
            } catch (Throwable) {
            }
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            UserEvent::USER_ACTIVATE => 'onUserActivate',
        ];
    }

}
