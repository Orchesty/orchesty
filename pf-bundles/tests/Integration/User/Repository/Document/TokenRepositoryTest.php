<?php declare(strict_types=1);

namespace Tests\Integration\User\Repository\Document;

use DateTime;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Repository\Document\TokenRepository;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class TokenRepositoryTest
 *
 * @package Tests\Integration\User\Repository\Document
 */
class TokenRepositoryTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     *
     */
    public function testGetFreshToken(): void
    {
        $token = new Token();
        $this->persistAndFlush($token);
        $this->dm->clear();

        /** @var TokenRepository $rep */
        $rep = $this->dm->getRepository(Token::class);
        self::assertNotNull($rep->getFreshToken($token->getId()));

        $this->setProperty($token, 'created', new DateTime('-2 days'));
        $this->persistAndFlush($token);
        $this->dm->clear();

        self::assertNull($rep->getFreshToken($token->getId()));
    }

}