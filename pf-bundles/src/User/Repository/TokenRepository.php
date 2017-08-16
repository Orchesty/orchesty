<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Repository;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\User\Document\Token;

/**
 * Class TokenRepository
 *
 * @package Hanaboso\PipesFramework\User\Repository
 */
class TokenRepository extends DocumentRepository
{

    /**
     * @param string $id
     *
     * @return Token|array|object|null
     */
    public function getFreshToken(string $id): ?Token
    {
        return $this->createQueryBuilder()
            ->field('id')
            ->equals($id)
            ->field('created')
            ->gte(new DateTime('-1 Day'))
            ->getQuery()
            ->getSingleResult();
    }

}