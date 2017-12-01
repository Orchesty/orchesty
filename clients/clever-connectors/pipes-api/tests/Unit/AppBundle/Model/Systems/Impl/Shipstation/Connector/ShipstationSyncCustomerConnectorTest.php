<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation\Connector;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Connector\ShipstationSyncCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\ShipstationSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class ShipstationSyncCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation\Connector
 */
final class ShipstationSyncCustomerConnectorTest extends KernelTestCaseAbstract
{

    /**
     */
    public function testProcessBatch(): void
    {
        $loop = Factory::create();

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode(['data' => ['system_install' => []], ['settings' => [], 'user' => '123']]));

        /** @var ShipstationSyncCustomerConnector $syncConn */
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
     * @return MockObject|ShipstationSyncCustomerConnector
     */
    private function mockSync()
    {
        $systemInstal = $this->createMock(SystemInstallRepository::class);
        $systemInstal->method('getSystemInstall')->willReturn((new SystemInstall()));

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstal);

        $lastSync = $this->createMock(LastSyncManager::class);
        $lastSync
            ->method('getLastSync')
            ->willReturn((new LastSync())->setTimestamp(new DateTime()));
        $lastSync
            ->method('updateLastSync')
            ->willReturn(NULL);

        $sender = $this->createMock(CurlSenderFactory::class);

        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        $syncConn = $this->getMockBuilder(ShipstationSyncCustomerConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $lastSync, $sender, $dm, $processCounter])
            ->getMock();

        $syncConn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode(['total' => 1]))));

        $syncConn->expects($this->at(1))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [],
                    json_encode(['customers' => [['email' => 'email@example.com']]])))
            );

        return $syncConn;
    }

    /**
     * @return MockObject|ShipstationSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://shipstation.com/'));
        $requestDto->setHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Basic token123',
        ]);
        $mock = $this->createMock(ShipstationSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}