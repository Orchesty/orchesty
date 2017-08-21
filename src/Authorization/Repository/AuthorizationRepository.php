<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/15/17
 * Time: 2:03 PM
 */

namespace Hanaboso\PipesFramework\Authorization\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class AuthorizationRepository
 *
 * @package Hanaboso\PipesFramework\Authorization\Repository
 */
class AuthorizationRepository extends DocumentRepository
{

    /**
     * @return string[]
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