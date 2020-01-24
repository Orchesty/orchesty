<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Unit\Authorization\Provider;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth1\OAuth1ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use Hanaboso\Utils\Exception\DateTimeException;
use OAuth;
use OAuthException;
use PHPUnit\Framework\MockObject\MockObject;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use ReflectionException;
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
     * @param string  $url
     * @param bool    $exception
     *
     * @throws Exception
     */
    public function testAuthorize(array $data, string $url, bool $exception): void
    {
        $install = new ApplicationInstall();
        /** @var OAuth1Provider|MockObject $provider */
        $provider = $this->getMockedProvider($data, $url);
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
            []
        );
        self::assertEmpty([]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::authorize
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
     * @return mixed[]
     */
    public function authorizeDataProvider(): array
    {
        return [
            [[], '', TRUE],
            [
                ['oauth_token' => 'token', 'oauth_token_secret' => 'secret'],
                'authorize/url?oauth_callback=https://example.com/api/applications/authorize/token&oauth_token=token',
                FALSE,
            ],
        ];
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
            [BasicApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::TOKEN => $data]]
        );
        /** @var OAuth1Provider|MockObject $provider */
        $provider = $this->getMockedProvider(['token'], '');
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
     */
    public function testGetAccessTokenErr(): void
    {
        $install = new ApplicationInstall();
        $install->setSettings(
            [
                BasicApplicationInterface::AUTHORIZATION_SETTINGS => [
                    BasicApplicationInterface::TOKEN => [
                        'oauth_token' => 'token', 'oauth_token_secret' => 'secret',
                    ],
                ],
            ]
        );
        $dto = new OAuth1Dto($install);

        /** @var OAuth1Provider|MockObject $oauth */
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
     * @return mixed[]
     */
    public function getAccessTokenDataProvider(): array
    {
        return [
            [[], [], TRUE],
            [['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], [], TRUE],
            [['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], ['oauth_verifier' => 'ver'], FALSE],
        ];
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
            [BasicApplicationInterface::AUTHORIZATION_SETTINGS => [BasicApplicationInterface::TOKEN => $data]]
        );
        /** @var OAuth1Provider|MockObject $provider */
        $provider = $this->getMockedProvider(['token'], '');
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
     * @return mixed[]
     */
    public function getHeaderDataProvider(): array
    {
        return [
            [[], TRUE],
            [['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], FALSE],
        ];
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider::createClient()
     *
     * @throws ReflectionException
     * @throws DateTimeException
     */
    public function testCreateClient(): void
    {
        $provider = self::$container->get('hbpf.providers.oauth1_provider');

        $dto = new OAuth1Dto((new ApplicationInstall())
            ->setSettings(
                [
                    BasicApplicationInterface::AUTHORIZATION_SETTINGS => [
                        OAuth1ApplicationInterface::CONSUMER_KEY    => 'consumer_key',
                        OAuth1ApplicationInterface::CONSUMER_SECRET => 'secret_key',
                    ],
                ]
            ));
        $this->invokeMethod($provider, 'createClient', [$dto]);

        self::assertFake();
    }


    /**
     * ---------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @param mixed[] $data
     * @param string  $authorizeUrl
     *
     * @return MockObject
     * @throws Exception
     */
    private function getMockedProvider(array $data, string $authorizeUrl): MockObject
    {
        $dm = self::createMock(DocumentManager::class);
        $dm->method('persist')->willReturn(TRUE);
        $dm->method('flush')->willReturn(TRUE);

        $redirect = self::createMock(RedirectInterface::class);
        $redirect->method('make')->with($authorizeUrl)->willReturnCallback(
            static function (): void {
            }
        );

        $oauth = self::createPartialMock(
            OAuth::class,
            ['getAccessToken', 'getRequestToken', 'setToken', 'getRequestHeader']
        );
        $oauth->method('getAccessToken')->willReturn($data);
        $oauth->method('getRequestToken')->willReturn($data);
        $oauth->method('setToken')->with('token', 'secret')->willReturn(TRUE);
        $oauth->method('getRequestHeader')->with('GET', 'someEndpoint/Url')->willReturn('generatedUrl');

        $client = self::getMockBuilder(OAuth1Provider::class)
            ->setConstructorArgs([$dm, $redirect, 'https://example.com'])
            ->setMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
