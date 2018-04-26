<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Repository\Entity;

use Doctrine\ORM\EntityRepository;
use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\UserBundle\Entity\UserInterface;

/**
 * Class GroupRepository
 *
 * @package Hanaboso\PipesFramework\Acl\Repository\Entity
 */
class GroupRepository extends EntityRepository
{

    /**
     * @param UserInterface $user
     *
     * @return Group[]
     */
    public function getUserGroups(UserInterface $user): array
    {
        return $this->createQueryBuilder('g')
            ->join('g.users', 'u')
            ->where('u = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

}