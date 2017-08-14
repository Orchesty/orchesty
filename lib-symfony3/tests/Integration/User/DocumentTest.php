<?php declare(strict_types=1);

namespace Tests\Integration\User;

use DateTime;
use Hanaboso\PipesFramework\User\Document\TmpUser;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Document\User;
use Tests\DatabaseTestCase;

/**
 * Class EntityTest
 *
 * @package Tests\Integration\User
 */
class DocumentTest extends DatabaseTestCase
{

    /**
     *
     */
    public function testReferences(): void
    {
        $tokenRepository = $this->documentManager->getRepository(Token::class);

        /** @var User $user */
        $user = (new User())->setEmail('email@example.com');

        /** @var TmpUser $tmpUser */
        $tmpUser = (new TmpUser())->setEmail('email@example.com');

        $this->documentManager->persist($user);
        $this->documentManager->persist($tmpUser);
        $this->documentManager->flush();

        $token = (new Token())
            ->setCreated(new DateTime('today midnight'))
            ->setUser($user)
            ->setTmpUser($tmpUser);

        $this->documentManager->persist($token);
        $this->documentManager->flush();
        $this->documentManager->clear();

        /** @var Token $existingToken */
        $existingToken = $tokenRepository->find($token->getId());

        $this->assertNotEmpty($existingToken->getUuid());
        $this->assertEquals($token->getCreated(), $existingToken->getCreated());
        $this->assertEquals($token->getUser()->getEmail(), $existingToken->getUser()->getEmail());
        $this->assertEquals($token->getTmpUser()->getEmail(), $existingToken->getTmpUser()->getEmail());
    }

}