<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 9.10.17
 * Time: 13:52
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\SalesForce\Connector;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce\Connector\SalesForceUpdateContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesForce\SalesForceSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use DateTime;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Repository\NodeRepository;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class SalesForceUpdateContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\SalesForce\Connector
 */
final class SalesForceUpdateContactConnectorTest extends KernelTestCaseAbstract
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

        $loop = Factory::create();

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders(['node_id' => '2234-adawad'])
            ->setData(json_encode($dtoData));

        /** @var SalesForceUpdateContactConnector $syncConn */
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
     * @return PHPUnit_Framework_MockObject_MockObject|SalesForceUpdateContactConnector
     */
    private function mockSync()
    {
        $node = $this->createMock(NodeRepository::class);
        $node->method('findOneBy')->willReturn((new Node())->setTopology('123456789')->setName('NAME'));

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

        $syncConn = $this->getMockBuilder(SalesForceUpdateContactConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $lastSync, $sender])
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