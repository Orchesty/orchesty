<?php declare(strict_types=1);

namespace Tests\Integration\User\Model\Token;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\User\Document\TmpUser;
use Hanaboso\PipesFramework\User\Document\Token;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Enum\UserTypeEnum;
use Hanaboso\PipesFramework\User\Model\Token\TokenManager;
use Hanaboso\PipesFramework\User\Model\Token\TokenManagerException;
use Hanaboso\PipesFramework\User\Repository\TokenRepository;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class TokenManagerTest
 *
 * @package Tests\Integration\User\Model\Token
 */
class TokenManagerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

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
        $this->tokenManager    = new TokenManager($this->dm);
        $this->tokenRepository = $this->dm->getRepository(Token::class);
    }

    /**
     * @covers TokenManager::create()
     */
    public function testCreateUserToken(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $this->tokenManager->create($user);
        $this->tokenManager->create($user);
        $this->tokenManager->create($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));
        $this->assertEquals($user->getEmail(), $token->getUser()->getEmail());
    }

    /**
     * @covers TokenManager::create()
     */
    public function testCreateTmpUserToken(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        $this->tokenManager->create($user);
        $this->tokenManager->create($user);
        $this->tokenManager->create($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));
        $this->assertEquals($user->getEmail(), $token->getTmpUser()->getEmail());;
    }

    /**
     * @covers TokenManager::validate()
     */
    public function testValidateToken(): void
    {
        $token = new Token();
        $this->persistAndFlush($token);

        /** @var Token $token */
        $token = $this->tokenRepository->find($token->getId());
        $token = $this->tokenManager->validate($token->getId());
        $this->assertInstanceOf(Token::class, $token);
    }

    /**
     * @covers TokenManager::validate()
     */
    public function testValidateInvalidToken(): void
    {
        $token = new Token();
        $this->setProperty($token, 'created', new DateTime('yesterday midnight'));
        $this->persistAndFlush($token);

        $this->expectException(TokenManagerException::class);
        $this->expectExceptionCode(TokenManagerException::TOKEN_NOT_VALID);

        $token = $this->tokenRepository->find($token->getId());
        $this->tokenManager->validate($token->getId());
    }

    /**
     * @covers TokenManager::delete()
     */
    public function testDeleteUserToken(): void
    {
        $user = (new User())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));

        $this->tokenManager->delete($token);
        $this->assertEquals(0, count($this->tokenRepository->findBy([UserTypeEnum::USER => $user])));
    }

    /**
     * @covers TokenManager::delete()
     */
    public function testDeleteTmpUserToken(): void
    {
        $user = (new TmpUser())->setEmail('email@example.com');
        $this->persistAndFlush($user);

        /** @var Token $token */
        $token = $this->tokenRepository->find($this->tokenManager->create($user)->getId());
        $this->assertEquals(1, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));

        $this->tokenManager->delete($token);
        $this->assertEquals(0, count($this->tokenRepository->findBy([UserTypeEnum::TMP_USER => $user])));
    }

}