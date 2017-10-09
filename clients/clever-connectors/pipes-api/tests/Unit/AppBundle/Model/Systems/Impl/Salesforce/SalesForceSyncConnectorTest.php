<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 9.10.17
 * Time: 13:17
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce;

use CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce\SalesForceSyncConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce\SalesForceSystem;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class SalesForceSyncConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce
 */
final class SalesForceSyncConnectorTest extends KernelTestCaseAbstract
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

        /** @var SalesForceSyncConnector $syncConn */
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
     * @return PHPUnit_Framework_MockObject_MockObject|SalesForceSyncConnector
     */
    private function mockSync()
    {
        $dm = $this->createMock(DocumentManager::class);

        $syncConn = $this->getMockBuilder(SalesForceSyncConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $dm])
            ->getMock();

        $syncConn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode(['totalSize' => 1]))));

        $syncConn->expects($this->at(1))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode(['records' => [['email' => 'aa@aa.com']]]))));

        return $syncConn;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|SalesForceSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://salesforce.com/'));
        $requestDto->setHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer token123',
        ]);
        $mock = $this->createMock(SalesForceSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}