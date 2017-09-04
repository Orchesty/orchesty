<?php declare(strict_types=1);

namespace Tests\Integration\User\Repository\Entity;

use DateTime;
use Hanaboso\PipesFramework\User\Entity\Token;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class TokenRepositoryTest
 *
 * @package Tests\Integration\User\Repository\Entity
 */
class TokenRepositoryTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     *
     */
    public function testGetFreshToken(): void
    {
        $em = $this->container->get('doctrine.orm.default_entity_manager');

        $token = new Token();
        $em->persist($token);
        $em->flush($token);
        $em->clear();

        self::assertNotNull($em->getRepository(Token::class)->getFreshToken($token->getId()));

        $this->setProperty($token, 'created', new DateTime('-2 days'));
        $em->persist($token);
        $em->flush($token);
        $em->clear();

        self::assertNull($em->getRepository(Token::class)->getFreshToken($token->getId()));
    }

}