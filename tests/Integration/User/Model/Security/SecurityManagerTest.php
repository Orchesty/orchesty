<?php declare(strict_types=1);

namespace Tests\Integration\User\Model\Security;

use Doctrine\Common\Persistence\ObjectRepository;
use Hanaboso\PipesFramework\User\Document\User;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManager;
use Hanaboso\PipesFramework\User\Model\Security\SecurityManagerException;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class SecurityManagerTest
 *
 * @package Tests\Integration\User\Model\Security
 */
class SecurityManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @var PasswordEncoderInterface
     */
    private $encoder;

    /**
     * @var SecurityManager
     */
    private $securityManager;

    /**
     * @var ObjectRepository
     */
    private $userRepository;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $encodeFactory         = $this->container->get('security.encoder_factory');
        $this->encoder         = $encodeFactory->getEncoder(User::class);
        $this->securityManager = new SecurityManager(
            $this->container->get('hbpf.database_manager_locator.user'),
            $encodeFactory,
            $this->session,
            $this->container->get('security.token_storage'),
            $this->container->get('hbpf.user.provider.resource')
        );
        $this->userRepository  = $this->dm->getRepository(User::class);
    }

    /**
     * @covers SecurityManager::login()
     */
    public function testLogin(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $user = $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        $this->assertEquals('email@example.com', $user->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));
    }

    /**
     * @covers SecurityManager::login()
     */
    public function testLoginInvalidEmail(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->expectException(SecurityManagerException::class);
        $this->expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);
        $this->securityManager->login(['email' => 'invalidEmail@example.com', 'password' => 'passw0rd']);
    }

    /**
     * @covers SecurityManager::login()
     */
    public function testLoginInvalidPassword(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->expectException(SecurityManagerException::class);
        $this->expectExceptionCode(SecurityManagerException::USER_OR_PASSWORD_NOT_VALID);
        $this->securityManager->login(['email' => 'no-email@example.com', 'password' => 'invalidPassw0rd']);
    }

    /**
     * @covers SecurityManager::isLoggedIn()
     */
    public function testIsLoggedIn(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        $this->assertTrue($this->session->has(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA));

        $token = unserialize($this->session->get(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA));
        /** @var User $user */
        $user = $this->userRepository->find($token->getUser()->getId());
        $this->assertEquals('email@example.com', $user->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));
    }

    /**
     *
     */
    public function testIsLoggedOut(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->assertFalse($this->session->has(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA));
        $this->assertNull($this->userRepository->find($this->session->get(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA)));
    }

    /**
     * @covers SecurityManager::logout()
     */
    public function testLogout(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);
        $this->assertTrue($this->session->has(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA));

        $token = unserialize($this->session->get(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA));

        /** @var User $user */
        $user = $this->userRepository->find($token->getUser()->getId());
        $this->assertEquals('email@example.com', $user->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));

        $this->securityManager->logout();
        $this->assertFalse($this->session->has(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA));
        $this->assertNull($this->userRepository->find($this->session->get(SecurityManager::SECURITY_KEY . SecurityManager::SECURED_AREA)));
    }

    /**
     * @covers SecurityManager::getLoggedUser()
     */
    public function testGetLoggedUser(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->securityManager->login(['email' => 'email@example.com', 'password' => 'passw0rd']);

        $user = $this->securityManager->getLoggedUser();
        $this->assertEquals('email@example.com', $user->getEmail());
        $this->assertTrue($this->encoder->isPasswordValid($user->getPassword(), 'passw0rd', ''));
    }

    /**
     * @covers SecurityManager::getLoggedUser()
     */
    public function testGetNotLoggedUser(): void
    {
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword($this->encoder->encodePassword('passw0rd', ''));
        $this->persistAndFlush($user);

        $this->expectException(SecurityManagerException::class);
        $this->expectExceptionCode(SecurityManagerException::USER_NOT_LOGGED);
        $this->securityManager->getLoggedUser();
    }

}