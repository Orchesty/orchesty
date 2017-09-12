<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Repository\Document;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;

/**
 * Class TokenRepository
 *
 * @package Hanaboso\PipesFramework\User\Repository\Document
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

    /**
     * @param UserInterface $user
     *
     * @return array
     */
    public function getExistingTokens(UserInterface $user): array
    {
        return $this->findBy([$user->getType() === UserTypeEnum::USER ? 'user' : 'tmpUser' => $user]);
    }

}