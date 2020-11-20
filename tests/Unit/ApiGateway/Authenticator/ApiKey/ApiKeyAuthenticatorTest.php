<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\ApiGateway\Authenticator\ApiKey;

use Exception;
use Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator;
use Hanaboso\UserBundle\Document\User;
use PipesFrameworkTests\KernelTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class ApiKeyAuthenticatorTest
 *
 * @package PipesFrameworkTests\Unit\ApiGateway\Authenticator\ApiKey
 */
final class ApiKeyAuthenticatorTest extends KernelTestCaseAbstract
{

    private const KEY = 'key';

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::start
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::__construct
     */
    public function testStart(): void
    {
        $res = $this->getAuthenticator()->start(new Request());
        self::assertEquals('{"message":"Authentication Required"}', $res->getContent());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::supports
     */
    public function testSupports(): void
    {
        $r = new Request();
        self::assertFalse($this->getAuthenticator()->supports($r));
        $r->headers->set(ApiKeyAuthenticator::AUTH_HEADER, self::KEY);
        self::assertTrue($this->getAuthenticator()->supports($r));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::getCredentials
     */
    public function testGetCredentials(): void
    {
        $r = new Request();
        self::assertEmpty($this->getAuthenticator()->getCredentials($r));
        $r->headers->set(ApiKeyAuthenticator::AUTH_HEADER, self::KEY);
        self::assertEquals(self::KEY, $this->getAuthenticator()->getCredentials($r));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::getUser
     */
    public function testGetUser(): void
    {
        /** @var User $u */
        $u = $this->getAuthenticator()->getUser(self::KEY, self::createMock(UserProviderInterface::class));
        self::assertEquals('apiUser', $u->getEmail());

        $u = $this->getAuthenticator()->getUser(NULL, self::createMock(UserProviderInterface::class));
        self::assertEmpty($u);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::checkCredentials
     */
    public function testCheckCredentials(): void
    {
        $r = $this->getAuthenticator()->checkCredentials(self::KEY, self::createMock(User::class));
        self::assertTrue($r);

        $r = $this->getAuthenticator()->checkCredentials('bad_key', self::createMock(User::class));
        self::assertFalse($r);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::onAuthenticationFailure
     */
    public function testOnAuthenticationFailure(): void
    {
        $res = $this->getAuthenticator()->onAuthenticationFailure(new Request(), new AuthenticationException('error'));
        self::assertEquals('{"message":"An authentication exception occurred."}', $res->getContent());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::onAuthenticationSuccess
     */
    public function testOnAuthenticationSuccess(): void
    {
        $res = $this->getAuthenticator()->onAuthenticationSuccess(
            new Request(),
            self::createMock(TokenInterface::class),
            'key'
        );
        self::assertEmpty($res);
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::supportsRememberMe
     */
    public function testSupportsRememberMe(): void
    {
        self::assertFalse($this->getAuthenticator()->supportsRememberMe());
    }

    /**
     * ------------------------------------- HELPERS -----------------------------------
     */

    /**
     * @return ApiKeyAuthenticator
     */
    private function getAuthenticator(): ApiKeyAuthenticator
    {
        return new ApiKeyAuthenticator(self::KEY);
    }

}
