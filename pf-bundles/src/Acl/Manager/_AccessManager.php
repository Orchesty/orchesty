<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Manager;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Acl\Annotation\OwnerAnnotation;
use Hanaboso\PipesFramework\Acl\Dto\GroupDto;
use Hanaboso\PipesFramework\Acl\Entity\GroupInterface;
use Hanaboso\PipesFramework\Acl\Entity\RuleInterface;
use Hanaboso\PipesFramework\Acl\Enum\ActionEnum;
use Hanaboso\PipesFramework\Acl\Enum\ResourceEnum;
use Hanaboso\PipesFramework\Acl\Exception\AclException;
use Hanaboso\PipesFramework\Acl\Factory\MaskFactory;
use Hanaboso\PipesFramework\Acl\Factory\RuleFactory;
use Hanaboso\PipesFramework\Acl\Provider\Impl\DatabaseProvider;
use Hanaboso\PipesFramework\Acl\Repository\Document\GroupRepository as DocumentGroupRepository;
use Hanaboso\PipesFramework\Acl\Repository\Entity\GroupRepository as EntityGroupRepository;
use Hanaboso\PipesFramework\HbPFAclBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\DatabaseManager\UserDatabaseManagerLocator;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Model\User\Event\UserEvent;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class AccessManager
 *
 * @package Hanaboso\PipesFramework\Acl\Manager
 */
class _AccessManager implements EventSubscriberInterface
{

    /**
     * @var DocumentManager|EntityManager
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
     * @var mixed
     */
    private $resEnum;

    /**
     * AccessManager constructor.
     *
     * @param UserDatabaseManagerLocator $userDml
     * @param RuleFactory                $factory
     * @param DatabaseProvider           $dbProvider
     * @param ResourceProvider           $resProvider
     * @param mixed                      $resEnum
     */
    function __construct(
        UserDatabaseManagerLocator $userDml,
        RuleFactory $factory,
        DatabaseProvider $dbProvider,
        ResourceProvider $resProvider,
        $resEnum
    )
    {
        $this->dm          = $userDml->get();
        $this->factory     = $factory;
        $this->dbProvider  = $dbProvider;
        $this->resProvider = $resProvider;
        $this->resEnum     = $resEnum;
    }

    /**
     * @param string        $act
     * @param string        $res
     * @param UserInterface $user
     * @param string|null   $id
     *
     * @return mixed
     * @throws AclException
     */
    public function isAllowed(string $act, string $res, UserInterface $user, ?string $id = NULL)
    {
        $this->checkParams($act, $res);
        $class = $this->resProvider->getResource($res);

        $rules   = $this->dbProvider->getRules($user);
        $rule    = NULL;
        $byte    = MaskFactory::getActionByte($act);
        $userLvl = 999;

        foreach ($rules as $val) {
            if ($this->hasRight($val, $res, $byte)) {
                $rule = $val;
                if ($rule->getGroup()->getLevel() < $userLvl) {
                    $userLvl = $rule->getGroup()->getLevel();
                }
                if (!property_exists($class, 'owner')) {
                    break;
                } else if ($val->getPropertyMask() === 2) {
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

        if (!$id && $act === ActionEnum::WRITE) {
            return TRUE;
        }

        $data = ['id' => $id];

        if ((new ReflectionClass($class))->hasProperty('owner') && $byte >= 0) {
            $reader = new AnnotationReader();

            $data['owner'] =
                $reader->getPropertyAnnotation(new ReflectionProperty($class, 'owner'), OwnerAnnotation::class)
                    ? $user
                    : $user->getId();
        }

        $val = $this->dm->getRepository($class)->findOneBy($data);

        if ($val) {
            if ($res === ResourceEnum::GROUP) {
                /** @var GroupInterface $val */
                $val = $this->hasRightForGroup($val, $userLvl);
            } else if ($res === ResourceEnum::USER) {
                /** @var UserInterface $val */
                $val = $this->hasRightForUser($val, $userLvl);
            }
        }

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
     * @param mixed         $object
     *
     * @return bool
     * @throws AclException
     */
    public function isAllowedEntity(string $act, string $res, UserInterface $user, $object): bool
    {
        if (!is_object($object)) {
            throw new AclException(
                'Not given object',
                AclException::PERMISSION
            );
        }

        $this->checkParams($act, $res);

        $rules = $this->dbProvider->getRules($user);
        $byte  = MaskFactory::getActionByte($act);

        foreach ($rules as $val) {
            if ($this->hasRight($val, $res, $byte)) {
                if (property_exists($object, 'owner')) {
                    if ($val->getPropertyMask() === 2) {
                        return TRUE;
                    } else {
                        if ($user === $object->getOwner()) {
                            return TRUE;
                        }
                    }
                } else {
                    return TRUE;
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
     * @return GroupInterface
     */
    public function addGroup(string $name): GroupInterface
    {
        $class = $this->resProvider->getResource(ResourceEnum::GROUP);
        /** @var GroupInterface $group */
        $group = new $class(NULL);
        $group->setName($name);
        $this->dm->persist($group);
        $this->dm->flush($group);

        return $group;
    }

    /**
     * @param GroupDto $data
     *
     * @return GroupInterface
     * @throws AclException
     */
    public function updateGroup(GroupDto $data): GroupInterface
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
     * @param GroupInterface $group
     */
    public function removeGroup(GroupInterface $group): void
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
        $user  = $event->getUser();
        $class = $this->resProvider->getResource(ResourceEnum::GROUP);
        /** @var GroupInterface $group */
        $group = new $class($user);
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
        if (!ActionEnum::isValid($act) || !($this->resEnum)::isValid($res)) {
            throw new AclException(
                'Invalid resource or action type.',
                AclException::INVALID_RESOURCE
            );
        }
    }

    /**
     * @param RuleInterface $rule
     * @param string        $res
     * @param int           $byte
     *
     * @return bool
     */
    private function hasRight(RuleInterface $rule, string $res, int $byte): bool
    {
        return $rule->getResource() === $res && $rule->getActionMask() >> $byte & 1;
    }

    /**
     * @param UserInterface $user
     * @param int           $userLvl
     *
     * @return UserInterface|null
     */
    private function hasRightForUser(UserInterface $user, int $userLvl): ?UserInterface
    {
        /** @var EntityGroupRepository|DocumentGroupRepository $repo */
        $repo   = $this->dm->getRepository($this->resProvider->getResource(ResourceEnum::GROUP));
        $groups = $repo->getUserGroups($user);

        foreach ($groups as $group) {
            if ($group->getLevel() < $userLvl) {
                return NULL;
            }
        }

        return $user;
    }

    /**
     * @param GroupInterface $group
     * @param int            $userLvl
     *
     * @return GroupInterface|null
     */
    private function hasRightForGroup(GroupInterface $group, int $userLvl): ?GroupInterface
    {
        return ($group->getLevel() < $userLvl) ? NULL : $group;
    }

}