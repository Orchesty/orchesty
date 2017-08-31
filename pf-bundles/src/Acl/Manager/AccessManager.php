<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Manager;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\Acl\Dto\GroupDto;
use Hanaboso\PipesFramework\Acl\Enum\ActionEnum;
use Hanaboso\PipesFramework\Acl\Enum\ResourceEnum;
use Hanaboso\PipesFramework\Acl\Exception\AclException;
use Hanaboso\PipesFramework\Acl\Factory\MaskFactory;
use Hanaboso\PipesFramework\Acl\Factory\RuleFactory;
use Hanaboso\PipesFramework\Acl\Provider\Impl\DatabaseProvider;
use Hanaboso\PipesFramework\HbPFAclBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Document\UserInterface;
use Hanaboso\PipesFramework\User\Model\User\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AccessManager
 *
 * @package Hanaboso\PipesFramework\Acl\Manager
 */
class AccessManager implements EventSubscriberInterface
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var RuleFactory
     */
    private $factory;

    /**
     * @var DatabaseProvider
     */
    private $dbProvider;

    /**
     * @var ResourceProvider
     */
    private $resProvider;

    /**
     * AccessManager constructor.
     *
     * @param DocumentManager  $dm
     * @param RuleFactory      $factory
     * @param DatabaseProvider $dbProvider
     * @param ResourceProvider $resProvider
     */
    function __construct(
        DocumentManager $dm,
        RuleFactory $factory,
        DatabaseProvider $dbProvider,
        ResourceProvider $resProvider
    )
    {
        $this->dm          = $dm;
        $this->factory     = $factory;
        $this->dbProvider  = $dbProvider;
        $this->resProvider = $resProvider;
    }

    /**
     * @param string        $act
     * @param string        $res
     * @param UserInterface $user
     * @param string        $id
     *
     * @return mixed
     * @throws AclException
     */
    public function isAllowed(string $act, string $res, UserInterface $user, string $id)
    {
        $this->checkParams($act, $res);

        $rules = $this->dbProvider->getRules($user);
        $rule  = NULL;
        $byte  = MaskFactory::getActionByte($act);
        /** @var Rule $val */
        foreach ($rules as $val) {
            if ($this->hasRight($val, $res, $byte)) {
                $rule = $val;
                if ($val->getPropertyMask() === 2) {
                    $byte = -1;
                    break;
                }
            }
        }

        if (!$rule) {
            throw new AclException(
                sprintf('User has no permission on [%s] resource for [%s] action.', $res, $act),
                AclException::PERMISSION
            );
        }

        $data = ['id' => $id];
        if ($byte >= 0) {
            $data['owner'] = $user;
        }
        $val = $this->dm->getRepository($this->resProvider->getResource($res))->findOneBy($data);

        if (!$val) {
            throw new AclException(
                sprintf('User has no permission or entity with given [%s] id doesn\'t exist.', $id),
                AclException::PERMISSION
            );
        }

        return $val;
    }

    /**
     * @param string        $act
     * @param string        $res
     * @param UserInterface $user
     * @param mixed         $document
     *
     * @return bool
     * @throws AclException
     */
    public function isAllowedEntity(string $act, string $res, UserInterface $user, $document): bool
    {
        $this->checkParams($act, $res);

        $rules = $this->dbProvider->getRules($user);
        $byte  = MaskFactory::getActionByte($act);

        /** @var Rule $val */
        foreach ($rules as $val) {
            if ($this->hasRight($val, $res, $byte)) {
                if ($val->getPropertyMask() === 2) {
                    return TRUE;
                } else {
                    if ($user === $document->getOwner()) {
                        return TRUE;
                    }
                }
            }
        }

        throw new AclException(
            sprintf('User has no permission on [%s] resource for [%s] action.', $res, $act),
            AclException::PERMISSION
        );
    }

    /**
     * @param string $name
     *
     * @return Group
     */
    public function addGroup(string $name): Group
    {
        $group = new Group(NULL);
        $group->setName($name);
        $this->dm->persist($group);
        $this->dm->flush($group);

        return $group;
    }

    /**
     * @param GroupDto $data
     *
     * @return Group
     * @throws AclException
     */
    public function updateGroup(GroupDto $data): Group
    {
        $group = $data->getGroup();

        if ($group->getRules()) {
            foreach ($group->getRules() as $rule) {
                $this->dm->remove($rule);
            }
        }

        if ($data->getName()) {
            $group->setName($data->getName());
        }

        $group->setUsers($data->getUsers());
        $group->setRules($data->getRules());

        if ($data->getRules()) {
            foreach ($data->getRules() as $rule) {
                $this->dm->persist($rule);
            }
        }

        $this->dm->flush();

        return $group;
    }

    /**
     * @param Group $group
     */
    public function removeGroup(Group $group): void
    {
        foreach ($group->getRules() as $rule) {
            $this->dm->remove($rule);
        }

        $this->dm->remove($group);
        $this->dm->flush();
    }

    /**
     * @param UserEvent $event
     */
    public function createGroup(UserEvent $event): void
    {
        /** @var User $user */
        $user  = $event->getUser();
        $group = new Group($user);
        $group
            ->setName($user->getEmail())
            ->addUser($user);

        $this->factory->getDefaultRules($group);
        $this->dm->flush();
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

    /**
     * @param string $act
     * @param string $res
     *
     * @throws AclException
     */
    private function checkParams(string $act, string $res): void
    {
        if (!ActionEnum::isValid($act) || !ResourceEnum::isValid($res)) {
            throw new AclException(
                'Invalid resource or action type.',
                AclException::INVALID_RESOURCE
            );
        }
    }

    /**
     * @param Rule   $rule
     * @param string $res
     * @param int    $byte
     *
     * @return bool
     */
    private function hasRight(Rule $rule, string $res, int $byte): bool
    {
        return $rule->getResource() === $res && $rule->getActionMask() >> $byte & 1;
    }

}