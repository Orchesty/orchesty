<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 9.10.17
 * Time: 13:17
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Connector\SalesforceSyncContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\SalesforceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class SalesforceSyncContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceSyncContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     */
    public function testProcessBatch(): void
    {
        $dtoData = [
            'data' => [
                'system_install' => ['user' => '123'],
                'topology'       => ['name' => 'top-name-ever'],
            ],
        ];

        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        /** @var SalesforceSyncContactConnector $syncConn */
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
     * @return PHPUnit_Framework_MockObject_MockObject|SalesforceSyncContactConnector
     */
    private function mockSync()
    {
        $systemInstal = $this->createMock(SystemInstallRepository::class);
        $systemInstal->method('setSyncTime')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstal);

        $sender = $this->createMock(CurlSenderFactory::class);

        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        $syncConn = $this->getMockBuilder(SalesforceSyncContactConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([
                $this->mockSystem(), $this->container->get('cc.last_sync.manager'), $sender, $dm, $processCounter,
            ])
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
     * @return PHPUnit_Framework_MockObject_MockObject|SalesforceSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://salesforce.com/'));
        $requestDto->setHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer token123',
        ]);
        $mock = $this->createMock(SalesforceSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}