<?php declare(strict_types=1);

namespace Tests\Unit\Authorization\Provider;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Redirect\RedirectInterface;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\AuthorizationException;
use Hanaboso\PipesPhpSdk\Authorization\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesPhpSdk\Authorization\Provider\OAuth1Provider;
use OAuth;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class OAuth1ProviderTest
 *
 * @package Tests\Unit\Authorization\Provider
 */
final class OAuth1ProviderTest extends TestCase
{

    /**
     * @dataProvider authorizeDataProvider
     *
     * @param array  $data
     * @param string $url
     * @param bool   $exception
     *
     * @throws Exception
     */
    public function testAuthorize(array $data, string $url, bool $exception): void
    {
        $install = new ApplicationInstall();
        /** @var OAuth1Provider|MockObject $provider */
        $provider = $this->getMockedProvider($data, $url);
        $dto      = new OAuth1Dto($install);

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $provider->authorize($dto, 'token/url', 'authorize/url', '127.0.0.4', function (): void {
        }, []);
    }

    /**
     * @return array
     */
    public function authorizeDataProvider(): array
    {
        return [
            [[], '', TRUE],
            [
                ['oauth_token' => 'token', 'oauth_token_secret' => 'secret'],
                'authorize/url?oauth_callback=127.0.0.4&oauth_token=token', FALSE,
            ],
        ];
    }

    /**
     * @dataProvider getAccessTokenDataProvider
     *
     * @param array $data
     * @param array $request
     * @param bool  $exception
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
        $dto      = new OAuth1Dto($install);

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $token = $provider->getAccessToken($dto, $request, 'accesToken/Url');

        self::assertNotEmpty($token);
        self::assertTrue(is_array($token));
    }

    /**
     * @return array
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
     * @dataProvider getHeaderDataProvider
     *
     * @param array $data
     * @param bool  $exception
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
        $dto      = new OAuth1Dto($install);

        if ($exception) {
            self::expectException(AuthorizationException::class);
            self::expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $header = $provider->getAuthorizeHeader($dto, 'GET', 'someEndpoint/Url');

        self::assertNotEmpty($header);
        self::assertStringStartsWith('ge', $header);
    }

    /**
     * @return array
     */
    public function getHeaderDataProvider(): array
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
     * @param array  $data
     * @param string $authorizeUrl
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
        $redirect->method('make')->with($authorizeUrl)->willReturnCallback(function (): void {
        });

        $oauth = self::createPartialMock(
            OAuth::class,
            ['getAccessToken', 'getRequestToken', 'setToken', 'getRequestHeader']
        );
        $oauth->method('getAccessToken')->willReturn($data);
        $oauth->method('getRequestToken')->willReturn($data);
        $oauth->method('setToken')->with('token', 'secret')->willReturn(TRUE);
        $oauth->method('getRequestHeader')->with('GET', 'someEndpoint/Url')->willReturn('generatedUrl');

        $client = self::getMockBuilder(OAuth1Provider::class)
            ->setConstructorArgs([$dm, $redirect])
            ->setMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}
