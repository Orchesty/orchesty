<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Provider\Impl;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Acl\Entity\RuleInterface;
use Hanaboso\PipesFramework\Acl\Provider\ProviderInterface;
use Hanaboso\PipesFramework\Acl\Repository\Document\GroupRepository as OdmRepo;
use Hanaboso\PipesFramework\Acl\Repository\Entity\GroupRepository as OrmRepo;
use Hanaboso\PipesFramework\HbPFUserBundle\Provider\ResourceProvider;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Enum\ResourceEnum;

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
    private $dm;

    /**
     * @var ResourceProvider
     */
    private $provider;

    /**
     * DatabaseProvider constructor.
     *
     * @param DatabaseManagerLocator $userDml
     * @param ResourceProvider       $provider
     */
    public function __construct(DatabaseManagerLocator $userDml, ResourceProvider $provider)
    {
        $this->dm       = $userDml->get();
        $this->provider = $provider;
    }

    /**
     * @param UserInterface $user
     *
     * @return RuleInterface[]
     */
    public function getRules(UserInterface $user): array
    {
        /** @var OrmRepo|OdmRepo $groupRepository */
        $groupRepository = $this->dm->getRepository($this->provider->getResource(ResourceEnum::GROUP));

        $rules = [];
        foreach ($groupRepository->getUserGroups($user) as $group) {
            foreach ($group->getRules() as $rule) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

}