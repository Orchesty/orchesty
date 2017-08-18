<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 18.8.17
 * Time: 10:30
 */

namespace Tests\Unit\Authorization\Provider;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Authorizations\Document\Authorization;
use Hanaboso\PipesFramework\Authorizations\Provider\Dto\OAuth1Dto;
use Hanaboso\PipesFramework\Authorizations\Provider\OAuth1Provider;
use Hanaboso\PipesFramework\Commons\Redirect\RedirectInterface;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Exception\AuthorizationException;
use OAuth;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class OAuth1ProviderTest
 *
 * @package Unit\Authorization\Provider
 */
final class OAuth1ProviderTest extends TestCase
{

    /**
     * @dataProvider authorizeDataProvider
     *
     * @param array  $data
     * @param string $url
     * @param bool   $exception
     */
    public function testAuthorize(array $data, string $url, bool $exception): void
    {
        $authorization = new Authorization('magento2.oauth');
        $authorization->setToken([]);
        /** @var OAuth1Provider|PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMockedProvider($data, $url);
        $dto      = new OAuth1Dto($authorization, 'key', 'sec');

        if ($exception) {
            $this->expectException(AuthorizationException::class);
            $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $provider->authorize($dto, 'token/url', 'authorize/url');
    }

    /**
     * @return array
     */
    public function authorizeDataProvider(): array
    {
        return [
            [[], '', TRUE],
            [['oauth_token' => 'token', 'oauth_token_secret' => 'secret'], 'authorize/url?oauth_token=token', FALSE],
        ];
    }

    /**
     * @dataProvider getAccessTokenDataProvider
     *
     * @param array $data
     * @param array $request
     * @param bool  $exception
     */
    public function testGetAccessToken(array $data, array $request, bool $exception): void
    {
        $authorization = new Authorization('magento2.oauth');
        $authorization->setToken($data);
        /** @var OAuth1Provider|PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMockedProvider(['token'], '');
        $dto      = new OAuth1Dto($authorization, 'key', 'sec');

        if ($exception) {
            $this->expectException(AuthorizationException::class);
            $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $token = $provider->getAccessToken($dto, $request, 'accesToken/Url');

        $this->assertNotEmpty($token);
        $this->assertTrue(is_array($token));
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
     */
    public function testGetAuthorizeHeader(array $data, bool $exception): void
    {
        $authorization = new Authorization('magento2.oauth');
        $authorization->setToken($data);
        /** @var OAuth1Provider|PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMockedProvider(['token'], '');
        $dto      = new OAuth1Dto($authorization, 'key', 'sec');

        if ($exception) {
            $this->expectException(AuthorizationException::class);
            $this->expectExceptionCode(AuthorizationException::AUTHORIZATION_OAUTH1_ERROR);
        }

        $header = $provider->getAuthorizeHeader($dto, 'GET', 'someEndpoint/Url');

        $this->assertNotEmpty($header);
        $this->assertStringStartsWith('ge', $header);
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
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockedProvider(array $data, string $authorizeUrl): PHPUnit_Framework_MockObject_MockObject
    {
        $dm = $this->createMock(DocumentManager::class);
        $dm->method('persist')->willReturn(TRUE);
        $dm->method('flush')->willReturn(TRUE);

        $redirect = $this->createMock(RedirectInterface::class);
        $redirect->method('make')->with($authorizeUrl)->willReturn(TRUE);

        $oauth = $this->createPartialMock(
            OAuth::class,
            ['getAccessToken', 'getRequestToken', 'setToken', 'getRequestHeader']
        );
        $oauth->method('getAccessToken')->willReturn($data);
        $oauth->method('getRequestToken')->willReturn($data);
        $oauth->method('setToken')->with('token', 'secret')->willReturn(TRUE);
        $oauth->method('getRequestHeader')->with('GET', 'someEndpoint/Url')->willReturn('generatedUrl');

        $client = $this->getMockBuilder(OAuth1Provider::class)
            ->setConstructorArgs([$dm, $redirect])
            ->setMethods(['createClient'])
            ->getMock();

        $client->method('createClient')->willReturn($oauth);

        return $client;
    }

}