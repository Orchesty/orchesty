<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Authorization\Provider;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesPhpSdk\Authorization\Wrapper\OAuth2Wrapper;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Monolog\Logger;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class OAuth2ProviderTest
 *
 * @package PipesPhpSdkTests\Unit\Authorization\Provider
 */
final class OAuth2ProviderTest extends KernelTestCaseAbstract
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
        $provider = $this->getMockedProvider($url);
        $install  = new ApplicationInstall();
        $dto      = new OAuth2Dto($install, 'authorize/url', 'token/url');
        $dto->setCustomAppDependencies(uniqid(), 'magento');

        $provider->authorize($dto, []);
        self::assertFake();
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
        $provider = $this->getMockedProvider($url);
        $install  = new ApplicationInstall();
        $dto      = new OAuth2Dto($install, 'authorize/url', 'token/url');

        $provider->authorize($dto, []);
        self::assertFake();
    }

    /**
     * @dataProvider getAccessTokenDataProvider
     *
     * @param mixed[] $request
     * @param bool    $exception
     *
     * @throws Exception
     */
    public function testGetAccessToken(array $request, bool $exception): void
    {
        $provider = $this->getMockedProvider('');
        $provider->setLogger(new Logger('logger'));
        $install = new ApplicationInstall();
        $dto     = new OAuth2Dto($install, 'authorize/url', 'token/url');

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH2_ERROR);
        }

        $token = $provider->getAccessToken($dto, $request);

        self::assertNotEmpty($token);
    }

    /**
     * @dataProvider refreshTokenDataProvider
     *
     * @param mixed[] $token
     * @param bool    $exception
     *
     * @throws Exception
     */
    public function testRefreshAccessToken(array $token, bool $exception): void
    {
        $provider = $this->getMockedProvider('');
        $provider->setLogger(new Logger('logger'));
        $install = new ApplicationInstall();
        $dto     = new OAuth2Dto($install, 'authorize/url', 'token/url');

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH2_ERROR);
        }

        $token = $provider->refreshAccessToken($dto, $token);

        self::assertNotEmpty($token);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider::stateDecode
     */
    public function testStateDecode(): void
    {
        $state = OAuth2Provider::stateDecode('ZXhhbXBsZQ,,');

        self::assertEquals(['example', ''], $state);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth2Provider::getTokenByGrant
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuthProviderAbstract::getRedirectUri
     *
     * @throws Exception
     */
    public function testGetTokenByGrant(): void
    {
        $oauth = self::createPartialMock(OAuth2Wrapper::class, ['getAccessToken']);
        $oauth->method('getAccessToken')->willThrowException(new IdentityProviderException('message', 5, ''));

        $provider = self::createPartialMock(OAuth2Provider::class, ['createClient']);
        $provider->expects(self::any())->method('createClient')->willReturn($oauth);
        $provider->setLogger(new Logger('logger'));

        $this->setProperty($provider, 'backend', '127.0.0.11');
        $uri = $provider->getRedirectUri();
        self::assertEquals('127.0.0.11/api/applications/authorize/token', $uri);

        self::expectException(AuthorizationException::class);
        $this->invokeMethod(
            $provider,
            'getTokenByGrant',
            [new OAuth2Dto(new ApplicationInstall(), '/url/', ''), 'grand'],
        );
    }

    /**
     * @return mixed[]
     */
    public static function authorizeDataProvider(): array
    {
        return [
            [
                'authorize/url?state=7403bf6b94330ff59bb941ed7418ae30&response_type=code&approval_prompt=auto&redirect_uri=127.0.0.4%2Fred&client_id=cl_id',
            ],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function getAccessTokenDataProvider(): array
    {
        return [
            [[], TRUE],
            [['code' => '456'], FALSE],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function refreshTokenDataProvider(): array
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
     * @return OAuth2Provider
     * @throws Exception
     */
    private function getMockedProvider(string $authorizeUrl): OAuth2Provider
    {
        $oauth = self::createPartialMock(OAuth2Wrapper::class, ['getAuthorizationUrl', 'getAccessToken']);
        $oauth->method('getAuthorizationUrl')->willReturn($authorizeUrl);
        $oauth->method('getAccessToken')->willReturn(new AccessToken(['access_token' => '123']));

        $client = self::getMockBuilder(OAuth2Provider::class)
            ->setConstructorArgs(['127.0.0.4'])
            ->onlyMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
