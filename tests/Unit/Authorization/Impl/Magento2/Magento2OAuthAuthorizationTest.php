<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/17/17
 * Time: 1:33 PM
 */

namespace Tests\Unit\Authorization\Impl\Magento2;

use Hanaboso\PipesFramework\Authorization\Base\AuthorizationInterface;
use Hanaboso\PipesFramework\Authorization\Document\Authorization;
use Hanaboso\PipesFramework\Authorization\Impl\Magento2\Magento2OAuthAuthorization;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth1Provider;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class Magento2OAuthAuthorizationTest
 *
 * @package Tests\Unit\Authorization\Impl\Magento2
 */
final class Magento2OAuthAuthorizationTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @covers Magento2OAuthAuthorization::getAuthorizationType()
     */
    public function testGetAuthorizationType(): void
    {
        $auth = $this->getMockedAuthorization();
        $type = $auth->getAuthorizationType();

        $this->assertEquals(AuthorizationInterface::OAUTH, $type);
    }

    /**
     * @covers Magento2OAuthAuthorization::getHeaders()
     */
    public function testGetHeaders(): void
    {
        $auth = $this->getMockedAuthorization();

        $headers = $auth->getHeaders('GET', 'http://magento.com');
        $expects = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer access_token',
        ];

        self::assertEquals($expects, $headers);
    }

    /**
     * @covers Magento2OAuthAuthorization::getUrl()
     */
    public function testGetUrl(): void
    {
        $auth = $this->getMockedAuthorization();
        $url  = $auth->getUrl();

        $this->assertEquals('url://magento2', $url);
    }

    /**
     * @covers Magento2OAuthAuthorization::isAuthorized()
     */
    public function testIsAuthorized(): void
    {
        $auth = $this->getMockedAuthorization();
        $this->assertTrue($auth->isAuthorized());
    }

    /**
     * @covers Magento2OAuthAuthorization::authorize()
     */
    public function testAuthorize(): void
    {
        $auth = $this->getMockedAuthorization();
        $this->assertEmpty($auth->authorize());
    }

    /**
     * @covers Magento2OAuthAuthorization::saveToken()
     */
    public function testSaveToken(): void
    {
        $auth = $this->getMockedAuthorization();
        $this->assertEmpty($auth->saveToken([]));
    }

    /**
     * ----------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @return Magento2OAuthAuthorization|PHPUnit_Framework_MockObject_MockObject
     */
    public function getMockedAuthorization(): PHPUnit_Framework_MockObject_MockObject
    {
        $provider = $this->createMock(OAuth1Provider::class);
        $provider->method('getAuthorizeHeader')->willReturn('Bearer access_token');

        /** @var Magento2OAuthAuthorization|PHPUnit_Framework_MockObject_MockObject $auth */
        $auth = $this->getMockBuilder(Magento2OAuthAuthorization::class)
            ->setMethods(['getToken'])
            ->setConstructorArgs(
                [
                    $this->dm,
                    $provider,
                    'magento2.oauth',
                    'Magento name',
                    'Magento dsc',
                    'url://magento2',
                    'api_key',
                    'secret_key',
                ]
            )
            ->getMock();

        $this->setProperty(
            $auth,
            'authorization',
            (new Authorization('magento2.oauth'))
                ->setToken(['oauth_token' => 'token', 'oauth_token_secret' => 'secret'])
        );

        return $auth;
    }

}