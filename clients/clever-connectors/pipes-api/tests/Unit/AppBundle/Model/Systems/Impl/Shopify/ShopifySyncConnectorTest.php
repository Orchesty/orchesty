<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 9.10.17
 * Time: 9:55
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySyncConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\ShopifySystem;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class ShopifySyncConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shopify
 */
final class ShopifySyncConnectorTest extends KernelTestCaseAbstract
{

    /**
     */
    public function testProcessBatch(): void
    {
        $loop = Factory::create();

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode(['settings' => [], 'user' => '123']));

        /** @var ShopifySyncConnector $syncConn */
        $syncConn = $this->mockSync();
        $data     = $syncConn->processBatch($processDto, $loop, function (): void {
        });

        $data->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {

                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|ShopifySyncConnector
     */
    private function mockSync()
    {
        $syncConn = $this->getMockBuilder(ShopifySyncConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem()])
            ->getMock();

        $syncConn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode(['count' => 1]))));

        $syncConn->expects($this->at(1))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode(['customers' => [['id' => 1]]]))));

        return $syncConn;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|ShopifySystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://shopify.com/'));
        $requestDto->setHeaders([
            'X-Shopify-Access-Token' => 'token123',
            'Content-Type'           => 'application/json',
        ]);
        $mock = $this->createMock(ShopifySystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}