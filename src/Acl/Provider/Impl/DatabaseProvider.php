<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Provider\Impl;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\Acl\Document\Rule;
use Hanaboso\PipesFramework\Acl\Provider\ProviderInterface;
use Hanaboso\PipesFramework\Acl\Repository\GroupRepository;
use Hanaboso\PipesFramework\User\DatabaseManager\UserDatabaseManagerLocator;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Document\UserInterface;

/**
 * Class DatabaseProvider
 *
 * @package Hanaboso\PipesFramework\Acl\Provider\Impl
 */
class DatabaseProvider implements ProviderInterface
{

    /**
     * @var DocumentManager|EntityManager
     */
    private $em;

    /**
     * DatabaseProvider constructor.
     *
     * @param UserDatabaseManagerLocator $databaseManagerLocator
     */
    public function __construct(UserDatabaseManagerLocator $databaseManagerLocator)
    {
        $this->em = $databaseManagerLocator->get();
    }

    /**
     * @param UserInterface|User $user
     *
     * @return Rule[]
     */
    public function getRules(UserInterface $user): array
    {
        /** @var GroupRepository $groupRepository */
        $groupRepository = $this->em->getRepository(Group::class);

        $rules = [];
        foreach ($groupRepository->getUserGroups($user) as $group) {
            foreach ($group->getRules() as $rule) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

}