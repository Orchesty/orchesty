<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation\Connector;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Connector\ShipstationUpdateCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\ShipstationSystem;
use CleverConnectors\AppBundle\Repository\LastSyncRepository;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use DateTime;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class ShipstationUpdateCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shipstation\Connector
 */
final class ShipstationUpdateCustomerConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $loop = Factory::create();

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders(['node_id' => '2234-awdawd'])
            ->setData(json_encode(['system_install' => []]));

        /** @var ShipstationUpdateCustomerConnector $syncConn */
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
     * @return PHPUnit_Framework_MockObject_MockObject|ShipstationUpdateCustomerConnector
     */
    private function mockSync()
    {
        $node = $this->createMock(NodeRepository::class);
        $node->method('findOneBy')->willReturn((new Node())->setTopology('123456789')->setName('NAME'));

        $topo = $this->createMock(TopologyRepository::class);
        $topo->method('findOneBy')->willReturn((new Topology())->setName('NAME'));

        $lastSync = $this->createMock(LastSyncRepository::class);
        $lastSync->method('getLastSyncTime')->willReturn((new LastSync())->setTimestamp(new DateTime()));

        $systemInstal = $this->createMock(SystemInstallRepository::class);
        $systemInstal->method('getSystemInstall')->willReturn((new SystemInstall())->setUser('12')->setToken('12')
            ->setSystem('123'));

        $lastSync = $this->createMock(LastSyncManager::class);
        $lastSync
            ->method('getLastSync')
            ->willReturn((new LastSync())->setTimestamp(new DateTime()));
        $lastSync
            ->method('updateLastSync')
            ->willReturn(NULL);

        $sender = $this->createMock(CurlSenderFactory::class);

        $syncConn = $this->getMockBuilder(ShipstationUpdateCustomerConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $lastSync, $sender])
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
     * @return PHPUnit_Framework_MockObject_MockObject|ShipstationSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://shipstation.com/'));
        $requestDto->setHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer token123',
        ]);
        $mock = $this->createMock(ShipstationSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}