<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\User\Repository\Entity;

use DateTime;
use Doctrine\ORM\EntityRepository;
use Hanaboso\PipesFramework\User\Entity\Token;
use Hanaboso\PipesFramework\User\Entity\UserInterface;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;

/**
 * Class TokenRepository
 *
 * @package Hanaboso\PipesFramework\User\Repository\Entity
 */
class TokenRepository extends EntityRepository
{

    /**
     * @param string $id
     *
     * @return Token|array|object|null
     */
    public function getFreshToken(string $id): ?Token
    {
        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->andWhere('t.created > :created')
            ->setParameter('id', $id)
            ->setParameter('created', new DateTime('-1 day'))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param UserInterface $user
     *
     * @return array
     */
    public function getExistingTokens(UserInterface $user): array
    {
        return $this->createQueryBuilder('t')
            ->join($user->getType() === UserTypeEnum::USER ? 't.user' : 't.tmpUser', 'u')
            ->where('u.id = :id')
            ->setParameter('id', $user->getId())
            ->getQuery()
            ->getArrayResult();
    }

}