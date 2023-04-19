<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Authorization\Provider;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use OAuth;
use OAuthException;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use Symfony\Bridge\Monolog\Logger;

/**
 * Class OAuth1ProviderTest
 *
 * @package PipesPhpSdkTests\Unit\Authorization\Provider
 */
final class OAuth1ProviderTest extends KernelTestCaseAbstract
{

    /**
     * @covers       \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider
     * @covers       \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::authorize
     * @covers       \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::getAuthorizeUrl
     *
     * @dataProvider authorizeDataProvider
     *
     * @param mixed[] $data
     * @param bool    $exception
     *
     * @throws Exception
     */
    public function testAuthorize(array $data, bool $exception): void
    {
        $install  = new ApplicationInstall();
        $provider = $this->getMockedProvider($data);
        $provider->setLogger(new Logger('logger'));
        $dto = new OAuth1Dto($install);

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $provider->authorize(
            $dto,
            'token/url',
            'authorize/url',
            static function (): void {
            },
            [],
        );
        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::authorize
     * @throws Exception
     */
    public function testAuthorizeErr(): void
    {
        $oauth = self::createPartialMock(OAuth::class, ['getRequestToken']);
        $oauth->method('getRequestToken')->willThrowException(new Exception());
        $provider = self::createPartialMock(OAuth1Provider::class, ['createClient']);
        $provider->method('createClient')->willReturn($oauth);
        $provider->setLogger(new Logger('logger'));

        self::expectException(AuthorizationException::class);
        self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        $provider->authorize(new OAuth1Dto(new ApplicationInstall()), '123', '/url/', static fn($result) => $result);
    }

    /**
     * @covers       \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::getAccessToken
     * @covers       \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::tokenAndSecretChecker
     *
     * @dataProvider getAccessTokenDataProvider
     *
     * @param mixed[] $data
     * @param mixed[] $request
     * @param bool    $exception
     *
     * @throws Exception
     */
    public function testGetAccessToken(array $data, array $request, bool $exception): void
    {
        $install = new ApplicationInstall();
        $install->setSettings(
            [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::TOKEN => $data]],
        );
        $provider = $this->getMockedProvider(['token']);
        $provider->setLogger(new Logger('logger'));
        $dto = new OAuth1Dto($install);

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $token = $provider->getAccessToken($dto, $request, 'accesToken/Url');

        self::assertNotEmpty($token);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::getAccessToken
     *
     * @throws Exception
     */
    public function testGetAccessTokenErr(): void
    {
        $install = new ApplicationInstall();
        $install->setSettings(
            [
                ApplicationInterface::AUTHORIZATION_FORM => [
                    ApplicationInterface::TOKEN => [
                        'oauth_token' => 'token', 'oauth_token_secret' => 'secret',
                    ],
                ],
            ],
        );
        $dto = new OAuth1Dto($install);

        $oauth = self::createPartialMock(OAuth::class, ['getAccessToken', 'setToken']);
        $oauth->expects(self::any())->method('getAccessToken')->willThrowException(new OAuthException());
        $oauth->expects(self::any())->method('setToken');

        $provider = self::createPartialMock(OAuth1Provider::class, ['createClient']);
        $provider->expects(self::any())->method('createClient')->willReturn($oauth);
        $provider->setLogger(new Logger('logger'));

        $this->expectException(AuthorizationException::class);
        $provider->getAccessToken($dto, ['oauth_verifier' => 'ver'], 'accessToken/Url');
    }

    /**
     * @covers       \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::getAuthorizeHeader
     * @covers       \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::tokenAndSecretChecker
     *
     * @dataProvider getHeaderDataProvider
     *
     * @param mixed[] $data
     * @param bool    $exception
     *
     * @throws Exception
     */
    public function testGetAuthorizeHeader(array $data, bool $exception): void
    {
        $install = new ApplicationInstall();
        $install->setSettings(
            [ApplicationInterface::AUTHORIZATION_FORM => [ApplicationInterface::TOKEN => $data]],
        );
        $provider = $this->getMockedProvider(['token']);
        $provider->setLogger(new Logger('logger'));
        $dto = new OAuth1Dto($install);

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $header = $provider->getAuthorizeHeader($dto, 'GET', 'someEndpoint/Url');

        self::assertNotEmpty($header);
        self::assertStringStartsWith('ge', $header);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::createClient
     *
     * @throws Exception
     */
    public function testCreateClient(): void
    {
        $provider = self::getContainer()->get('hbpf.providers.oauth1_provider');

        $dto = new OAuth1Dto(
            (new ApplicationInstall())
                ->setSettings(
                    [
                        ApplicationInterface::AUTHORIZATION_FORM => [
                            OAuth1ApplicationInterface::CONSUMER_KEY    => 'consumer_key',
                            OAuth1ApplicationInterface::CONSUMER_SECRET => 'secret_key',
                        ],
                    ],
                ),
        );
        $this->invokeMethod($provider, 'createClient', [$dto]);

        self::assertFake();
    }

    /**
     * @return mixed[]
     */
    public static function authorizeDataProvider(): array
    {
        return [
            [[], TRUE],
            [['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], FALSE],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function getAccessTokenDataProvider(): array
    {
        return [
            [[], [], TRUE],
            [['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], [], TRUE],
            [['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], ['oauth_verifier' => 'ver'], FALSE],
        ];
    }

    /**
     * @return mixed[]
     */
    public static function getHeaderDataProvider(): array
    {
        return [
            [[], TRUE],
            [['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], FALSE],
        ];
    }

    /**
     * ---------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @param mixed[] $data
     *
     * @return OAuth1Provider
     * @throws Exception
     */
    private function getMockedProvider(array $data): OAuth1Provider
    {
        $oauth = self::createPartialMock(
            OAuth::class,
            ['getAccessToken', 'getRequestToken', 'setToken', 'getRequestHeader'],
        );
        $oauth->method('getAccessToken')->willReturn($data);
        $oauth->method('getRequestToken')->willReturn($data);
        $oauth->method('setToken')->with('token', 'secret')->willReturn(TRUE);
        $oauth->method('getRequestHeader')->with('GET', 'someEndpoint/Url')->willReturn('generatedUrl');

        $client = self::getMockBuilder(OAuth1Provider::class)
            ->setConstructorArgs(
                [
                    'https://example.com',
                    self::getContainer()->get('hbpf.application_install.repository'),
                ],
            )
            ->onlyMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
