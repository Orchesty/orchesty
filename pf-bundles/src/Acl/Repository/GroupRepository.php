<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Acl\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Query\Query;
use Hanaboso\PipesFramework\Acl\Document\Group;
use Hanaboso\PipesFramework\User\Document\UserInterface;

/**
 * Class GroupRepository
 *
 * @package Hanaboso\PipesFramework\Acl\Repository
 */
class GroupRepository extends DocumentRepository
{

    /**
     * @param UserInterface $user
     *
     * @return Group[]
     */
    public function getUserGroups(UserInterface $user): array
    {
        /** @var Query $query */
        $query = $this->createQueryBuilder()
            ->field('users')
            ->includesReferenceTo($user)
            ->getQuery()
            ->execute();

        return $query->toArray();
    }

}