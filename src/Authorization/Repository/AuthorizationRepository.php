<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Authorization\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * Class AuthorizationRepository
 *
 * @package Hanaboso\PipesFramework\Authorization\Repository
 */
class AuthorizationRepository extends DocumentRepository
{

    /**
     * @return string[]
     * @throws MongoDBException
     */
    public function getInstalledKeys(): array
    {
        $res = $this
            ->createQueryBuilder()
            ->distinct('authorizationKey')
            ->getQuery()
            ->execute();

        return $res->toArray();
    }

}
