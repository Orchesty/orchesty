<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\ApiGateway\Authenticator\ApiKey;

use Exception;
use Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator;
use PipesFrameworkTests\KernelTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

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
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::supports
     */
    public function testSupports(): void
    {
        $r = new Request();
        self::assertFalse($this->getAuthenticator()->supports($r));

        $r->headers->set(ApiKeyAuthenticator::AUTH_HEADER, self::KEY);
        self::assertTrue($this->getAuthenticator()->supports($r));

        $r->headers->remove(ApiKeyAuthenticator::AUTH_HEADER);
        $r->headers->set(ApiKeyAuthenticator::AUTHORIZATION, self::KEY);
        self::assertTrue($this->getAuthenticator()->supports($r));
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::authenticate
     */
    public function testAuthenticate(): void
    {
        $r = new Request();
        $r->headers->set(ApiKeyAuthenticator::AUTH_HEADER, self::KEY);
        $res = $this->getAuthenticator()->authenticate($r);
        self::assertEquals('apiUser', $res->getUser()->getUserIdentifier());
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator::authenticate
     */
    public function testAuthenticateErr(): void
    {
        $r = new Request();
        $r->headers->set(ApiKeyAuthenticator::AUTH_HEADER, 'badKEz');

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Not valid token');
        $this->getAuthenticator()->authenticate($r);
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
            'key',
        );
        self::assertEmpty($res);
    }

    /**
     * ------------------------------------- HELPERS -----------------------------------
     */

    /**
     * @return ApiKeyAuthenticator
     */
    private function getAuthenticator(): ApiKeyAuthenticator
    {
        return new ApiKeyAuthenticator(
            self::getContainer()->get('Hanaboso\UserBundle\Model\Security\JWTAuthenticator'),
            self::KEY,
        );
    }

}
