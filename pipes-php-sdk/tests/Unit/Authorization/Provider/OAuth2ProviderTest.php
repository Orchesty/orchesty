<?php declare(strict_types=1);

namespace Tests\Unit\Authorization\Provider;

use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\Authorization\Wrapper\OAuth2Wrapper;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class OAuth2ProviderTest
 *
 * @package Tests\Unit\Authorization\Provider
 */
final class OAuth2ProviderTest extends TestCase
{

    /**
     * @dataProvider authorizeDataProvider
     *
     * @param string $url
     *
     * @throws Exception
     */
    public function testAuthorize(string $url): void
    {
        /** @var MockObject|OAuth2Provider $provider */
        $provider = $this->getMockedProvider($url);
        $install  = new ApplicationInstall();
        $dto      = new OAuth2Dto($install, '127.0.0.4/red', 'authorize/url', 'token/url');
        $dto->setCustomAppDependencies(uniqid(), 'magento');

        $provider->authorize($dto, []);
    }

    /**
     * @dataProvider authorizeDataProvider
     *
     * @param string $url
     *
     * @throws Exception
     */
    public function testAuthorizeCustomApp(string $url): void
    {
        /** @var MockObject|OAuth2Provider $provider */
        $provider = $this->getMockedProvider($url);
        $install  = new ApplicationInstall();
        $dto      = new OAuth2Dto($install, '127.0.0.4/red', 'authorize/url', 'token/url');

        $provider->authorize($dto, []);
    }

    /**
     * @return array
     */
    public function authorizeDataProvider(): array
    {
        return [
            [
                'authorize/url?state=7403bf6b94330ff59bb941ed7418ae30&response_type=code&approval_prompt=auto&redirect_uri=127.0.0.4%2Fred&client_id=cl_id',
            ],
        ];
    }

    /**
     * @dataProvider getAccessTokenDataProvider
     *
     * @param array $request
     * @param bool  $exception
     *
     * @throws Exception
     */
    public function testGetAccessToken(array $request, bool $exception): void
    {
        /** @var OAuth2Provider|MockObject $provider */
        $provider = $this->getMockedProvider('');
        $provider->setLogger(new Logger('logger'));
        $install = new ApplicationInstall();
        $dto     = new OAuth2Dto($install, '127.0.0.4/red', 'authorize/url', 'token/url');

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH2_ERROR);
        }

        $token = $provider->getAccessToken($dto, $request);

        self::assertNotEmpty($token);
        self::assertTrue(is_array($token));
    }

    /**
     * @return array
     */
    public function getAccessTokenDataProvider(): array
    {
        return [
            [[], TRUE],
            [['code' => '456'], FALSE],
        ];
    }

    /**
     * @dataProvider refreshTokenDataProvider
     *
     * @param array $token
     * @param bool  $exception
     *
     * @throws Exception
     */
    public function testRefreshAccessToken(array $token, bool $exception): void
    {
        /** @var OAuth2Provider|MockObject $provider */
        $provider = $this->getMockedProvider('');
        $provider->setLogger(new Logger('logger'));
        $install = new ApplicationInstall();
        $dto     = new OAuth2Dto($install, '127.0.0.4/red', 'authorize/url', 'token/url');

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH2_ERROR);
        }

        $token = $provider->refreshAccessToken($dto, $token);

        self::assertNotEmpty($token);
        self::assertTrue(is_array($token));
    }

    /**
     * @return array
     */
    public function refreshTokenDataProvider(): array
    {
        return [
            [[], TRUE],
            [['refresh_token' => '789'], FALSE],
        ];
    }


    /**
     * ---------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @param string $authorizeUrl
     *
     * @return MockObject
     * @throws Exception
     */
    private function getMockedProvider(string $authorizeUrl): MockObject
    {
        $redirect = self::createMock(RedirectInterface::class);
        $redirect->method('make')->willReturnCallback(
            function (): void {
            }
        );

        $oauth = self::createPartialMock(OAuth2Wrapper::class, ['getAuthorizationUrl', 'getAccessToken']);
        $oauth->method('getAuthorizationUrl')->willReturn($authorizeUrl);
        $oauth->method('getAccessToken')->willReturn(new AccessToken(['access_token' => '123']));

        $client = self::getMockBuilder(OAuth2Provider::class)
            ->setConstructorArgs([$redirect, '127.0.0.4'])
            ->setMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
