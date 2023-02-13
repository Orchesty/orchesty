<?php declare(strict_types=1);

namespace PipesFrameworkTests\Unit\ApiGateway\Authenticator\ApiKey;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Exception;
use Hanaboso\PipesFramework\ApiGateway\Authenticator\ApiKey\ApiKeyAuthenticator;
use Hanaboso\PipesFramework\Configurator\Document\ApiToken;
use Hanaboso\PipesFramework\Configurator\Enum\ApiTokenScopesEnum;
use Hanaboso\PipesFramework\Configurator\Model\ApiTokenManager;
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
     * @covers \Hanaboso\PipesFramework\Configurator\Model\ApiTokenManager::create
     * @covers \Hanaboso\PipesFramework\Configurator\Model\ApiTokenManager::delete
     * @covers \Hanaboso\PipesFramework\Configurator\Model\ApiTokenManager::getAllBy
     */
    public function testAuthenticate(): void
    {
        /** @var ApiTokenManager $apiTokenManager */
        $apiTokenManager = self::getContainer()->get('hbpf.configurator.manager.api_token');
        $tokenResult     = $apiTokenManager->create([ApiToken::SCOPES => ApiTokenScopesEnum::getChoices()], '');
        $apiToken        = $tokenResult[ApiTokenManager::CREATED_TOKEN];

        $r = new Request();
        $r->headers->set(ApiKeyAuthenticator::AUTH_HEADER, $apiToken->getKey());
        $res = $this->getAuthenticator()->authenticate($r);
        self::assertEquals('apiUser', $res->getUser()->getUserIdentifier());

        $apiTokenManager->delete($apiToken);
        $this->expectException(DocumentNotFoundException::class);
        $this->expectExceptionMessage(sprintf("Document ApiToken with key '%s' not found!", $apiToken->getId()));
        $apiTokenManager->getOne($apiToken->getId());
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
            self::getContainer()->get('hbpf.database_manager_locator'),
        );
    }

}
