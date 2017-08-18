<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/17/17
 * Time: 3:15 PM
 */

namespace Tests\Integration\Authorization\Impl\Magento2;

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
 * @package Tests\Integration\Authorization\Impl\Magento2
 */
class Magento2AuthorizationTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

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

        /** @var Magento2Authorization $auth */
        $auth = $this->getMockedAuth();
        $res  = $auth->getHeaders('GET', 'url');

        self::assertEquals($expects, $res);
    }

    /**
     * @return Magento2Authorization
     */
    private function getMockedAuth(): Magento2Authorization
    {
        $response = $this->createPartialMock(ResponseDto::class, ['getBody']);
        $response->method('getBody')->willReturn('{"token":"tokenizer"}');

        /** @var CurlManagerInterface|PHPUnit_Framework_MockObject_MockObject $curl */
        $curl = $this->createPartialMock(CurlManagerInterface::class, ['send']);
        $curl->method('send')->willReturn($response);

        return new Magento2Authorization(
            $this->container->get('doctrine_mongodb.odm.default_document_manager'),
            $curl,
            'magento2.auth',
            'Magento2 auth',
            'Magento2 auth',
            'url://magento2',
            'username',
            'password'
        );
    }

}