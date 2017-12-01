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
     * @param string $hash
     *
     * @return Token|null
     */
    public function getFreshToken(string $hash): ?Token
    {
        /** @var Token $token */
        $token = $this->createQueryBuilder()
            ->field('hash')->equals($hash)
            ->field('created')->gte(new DateTime('-1 Day'))
            ->getQuery()
            ->getSingleResult();

        return $token;
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