<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector\AirtableSyncContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class AirtableSyncContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
final class AirtableSyncContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers AirtableSyncContactConnector::processBatch()
     */
    public function testProcessBatch(): void
    {
        $loop = Factory::create();

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([
                CMHeaders::createKey(AirtableSystem::TABLE_URL) => 'some/table',
            ])
            ->setData(json_encode(['data' => ['system_install' => []], ['settings' => [], 'user' => '123']]));

        /** @var AirtableSyncContactConnector $syncConn */
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
     * @covers AirtableSyncContactConnector::processBatch()
     */
    public function testProcessBatchLimit(): void
    {
        $loop = Factory::create();

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([
                CMHeaders::createKey(AirtableSystem::TABLE_URL) => 'some/table',
            ])
            ->setData(json_encode(['data' => ['system_install' => []], ['settings' => [], 'user' => '123']]));

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlSender::class);
        $sender
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (RequestDto $requestDto) {
                return reject(new Response(429));
            });

        /** @var MockObject|CurlSenderFactory $factory */
        $factory = $this->createMock(CurlSenderFactory::class);
        $factory
            ->method('create')
            ->willReturn($sender);

        $syncConn = new AirtableSyncContactConnector(
            $this->mockSystem(),
            $this->mockLastSync(),
            $factory,
            $this->mockDm(),
            $this->mockProcessCounter()
        );
        $data     = $syncConn->processBatch($processDto, $loop, function (): void {
        });

        $data->then(
            function (): void {
                $this->assertTrue(FALSE);
            },
            function (): void {
                $this->assertTrue(TRUE);
            }
        )->done();

        $loop->run();
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|AirtableSyncContactConnector
     */
    private function mockSync()
    {
        $sender   = $this->createMock(CurlSenderFactory::class);
        $syncConn = $this->getMockBuilder(AirtableSyncContactConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([
                $this->mockSystem(), $this->mockLastSync(), $sender, $this->mockDm(), $this->mockProcessCounter(),
            ])
            ->getMock();

        $syncConn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [],
                    json_encode([
                        'records' => [
                            [
                                'fields' => [
                                    'Name'  => 'abc',
                                    'Email' => 'a@a.com',
                                ],
                            ],
                        ],
                    ])))
            );

        return $syncConn;
    }

    /**
     * @return MockObject|DocumentManager
     */
    private function mockDm()
    {
        $systemInstal = $this->createMock(SystemInstallRepository::class);
        $systemInstal->method('getSystemInstall')->willReturn((new SystemInstall()));

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstal);

        return $dm;
    }

    /**
     * @return MockObject|LastSync
     */
    private function mockLastSync()
    {
        $lastSync = $this->createMock(LastSyncManager::class);
        $lastSync
            ->method('getLastSync')
            ->willReturn((new LastSync())->setTimestamp(new DateTime()));
        $lastSync
            ->method('updateLastSync')
            ->willReturn(NULL);

        return $lastSync;
    }

    /**
     * @return MockObject|ProgressCounterService
     */
    private function mockProcessCounter()
    {
        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        return $processCounter;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|AirtableSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://airtable.com/'));
        $requestDto->setHeaders([]);
        $mock = $this->createMock(AirtableSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}