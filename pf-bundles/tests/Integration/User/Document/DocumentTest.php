<?php declare(strict_types=1);

namespace Tests\Integration\User\Document;

use Hanaboso\PipesFramework\User\Document\TmpUser;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Document\User;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class DocumentTest
 *
 * @package Tests\Integration\User\Document
 */
class DocumentTest extends DatabaseTestCaseAbstract
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
            ->setTmpUser($tmpUser)
            ->setUser($user);

        $this->documentManager->persist($token);
        $this->documentManager->flush();
        $this->documentManager->clear();

        /** @var Token $existingToken */
        $existingToken = $tokenRepository->find($token->getId());

        $this->assertEquals(
            $token->getCreated()->format('d. m. Y H:i:s'),
            $existingToken->getCreated()->format('d. m. Y H:i:s')
        );
        $this->assertEquals($token->getUser()->getEmail(), $existingToken->getUser()->getEmail());
        $this->assertEquals($token->getTmpUser()->getEmail(), $existingToken->getTmpUser()->getEmail());

        $this->documentManager->remove($existingToken->getUser());
        $this->documentManager->remove($existingToken->getTmpUser());
        $this->documentManager->remove($existingToken);
        $this->documentManager->flush();
    }

}