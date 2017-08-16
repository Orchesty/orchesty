<?php declare(strict_types=1);

namespace Tests\Integration\User;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Enum\UserTypeEnum;
use Hanaboso\PipesFramework\HbPFUserBundle\Manager\TokenManager;
use Hanaboso\PipesFramework\HbPFUserBundle\Manager\TokenManagerException;
use Hanaboso\PipesFramework\User\Document\TmpUser;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Repository\TokenRepository;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class ManagerTest
 *
 * @package Integration\User
 */
class TokenManagerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var Token
     */
    private $token;

    /**
     * @var TokenManager
     */
    private $tokenManager;

    /**
     * @var TokenRepository|DocumentRepository
     */
    private $tokenRepository;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager    = new TokenManager($this->documentManager);
        $this->tokenRepository = $this->documentManager->getRepository(Token::class);
    }

    /**
     *
     */
    public function testCreateUserToken(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->documentManager->persist($user);
        $this->documentManager->flush();

        $this->tokenManager->create($user);
        $this->tokenManager->create($user);
        $this->tokenManager->create($user);

        $this->token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));
        $this->assertEquals($user->getEmail(), $this->token->getUser()->getEmail());

        $this->documentManager->remove($user);
    }

    /**
     *
     */
    public function testCreateTmpUserToken(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->documentManager->persist($user);
        $this->documentManager->flush();

        $this->tokenManager->create($user);
        $this->tokenManager->create($user);
        $this->tokenManager->create($user);

        $this->token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));
        $this->assertEquals($user->getEmail(), $this->token->getTmpUser()->getEmail());

        $this->documentManager->remove($user);
    }

    /**
     *
     */
    public function testValidateFreshToken(): void
    {
        $token = new Token();

        $this->documentManager->persist($token);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->token = $this->tokenRepository->find($token->getId());
        $this->tokenManager->validate($this->token->getId());
    }

    /**
     *
     */
    public function testValidateNotFreshToken(): void
    {
        $token = new Token();
        $this->setProperty($token, 'created', new DateTime('yesterday midnight'));

        $this->documentManager->persist($token);
        $this->documentManager->flush();
        $this->documentManager->clear();

        $this->expectException(TokenManagerException::class);
        $this->expectExceptionCode(TokenManagerException::TOKEN_NOT_VALID);

        $this->token = $this->tokenRepository->find($token->getId());
        $this->tokenManager->validate($this->token->getId());
    }

    /**
     *
     */
    public function testDeleteUserToken(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->documentManager->persist($user);
        $this->documentManager->flush();

        $this->token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));

        $this->tokenManager->delete($this->token);
        $this->assertEquals(0, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));

        $this->documentManager->remove($user);
    }

    /**
     *
     */
    public function testDeleteTmpUserToken(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->documentManager->persist($user);
        $this->documentManager->flush();

        $this->token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));

        $this->tokenManager->delete($this->token);
        $this->assertEquals(0, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));

        $this->documentManager->remove($user);
    }

    /**
     *
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->documentManager->remove($this->token);
        $this->documentManager->flush();
    }

}