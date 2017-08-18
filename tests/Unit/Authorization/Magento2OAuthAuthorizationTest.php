<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/17/17
 * Time: 1:33 PM
 */

namespace Tests\Unit\Authorization;

use Hanaboso\PipesFramework\Authorizations\Document\Authorization;
use Hanaboso\PipesFramework\Authorizations\Impl\Magento2\Magento2OAuthAuthorization;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class Magento2OAuthAuthorizationTest
 *
 * @package Tests\Unit\Authorization
 */
class Magento2OAuthAuthorizationTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var Magento2OAuthAuthorization|PHPUnit_Framework_MockObject_MockObject
     */
    private $auth;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = $this->getMockClass(Magento2OAuthAuthorization::class, ['getParam', 'getToken']);

        $this->auth = new $this->auth(
            $this->container->get('doctrine_mongodb.odm.default_document_manager'),
            'magento2.oauth',
            'url://magento2',
            'api_key',
            'secret_key'
        );

        $this->auth->method('getParam')->willReturn('access_token');
        $this->auth->method('getToken')->willReturn('');

        $this->setProperty(
            $this->auth,
            'authorization',
            (new Authorization('magento2.oauth'))->setToken(['mock'])
        );
    }

    /**
     * @covers Magento2OAuthAuthorization::getHeaders()
     */
    public function testGetHeaders(): void
    {
        $headers = $this->auth->getHeaders();
        $expects = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . 'access_token',
        ];

        self::assertEquals($expects, $headers);
    }

}