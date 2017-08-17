<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/17/17
 * Time: 3:15 PM
 */

namespace Tests\Integration\Authorization;

use Hanaboso\PipesFramework\Authorizations\Impl\Magento2\Magento2Authorization;
use Hanaboso\PipesFramework\Commons\Authorization\Connectors\AuthorizationAbstract;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class AuthorizationDBTest
 *
 * @package Tests\Integration\Authorization
 */
class AuthorizationDBTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @var Magento2Authorization|PHPUnit_Framework_MockObject_MockObject
     */
    private $auth;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = $this->getMockClass(Magento2Authorization::class, ['fake']);

        $response = $this->createPartialMock(ResponseDto::class, ['getBody']);
        $response->method('getBody')->willReturn('{"token":"tokenizer"}');

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createPartialMock(CurlManagerInterface::class, ['send']);
        $curl->method('send')->willReturn($response);

        $this->auth = new $this->auth(
            $this->container->get('doctrine_mongodb.odm.default_document_manager'),
            $curl,
            'magento2.auth',
            'url://magento2',
            'username',
            'password'
        );

        // Without mocking method not event getHeaders is called
        $this->auth->method('fake')->willReturn('fake');
    }

    /**
     * @covers AuthorizationAbstract::save()
     * @covers AuthorizationAbstract::load()
     * @covers AuthorizationAbstract::isAuthorized()
     * @covers Magento2Authorization::authenticate()
     * @covers Magento2Authorization::getHeaders()
     */
    public function testSaveLoad(): void
    {
        $expects = [
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer tokenizer',
        ];
        $res = $this->auth->getHeaders();

        self::assertEquals($expects, $res);
    }

}