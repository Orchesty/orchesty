<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Document\LastSync;
use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\AirtableSystem;
use CleverConnectors\AppBundle\Model\Systems\Impl\Airtable\Connector\AirtableUpdatedContactConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class AirtableUpdatedContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
final class AirtableUpdatedContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers AirtableUpdatedContactConnector::processBatch()
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

        /** @var AirtableUpdatedContactConnector $syncConn */
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

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlSender $sender */
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

        $syncConn = new AirtableUpdatedContactConnector(
            $this->mockSystem(),
            $this->mockLastSync(),
            $factory,
            $this->mockDm()
        );

        $data = $syncConn->processBatch($processDto, $loop, function (): void {
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
     * @return PHPUnit_Framework_MockObject_MockObject|AirtableUpdatedContactConnector
     */
    private function mockSync()
    {
        $sender   = $this->createMock(CurlSenderFactory::class);
        $syncConn = $this->getMockBuilder(AirtableUpdatedContactConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([
                $this->mockSystem(), $this->mockLastSync(), $sender, $this->mockDm(),
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
        $sys = new SystemInstall();
        $sys->setSettings([
            SystemInstall::FORMS => [
                [
                    'table-url' => 'http://someTable',
                    'list-id'   => 'listId',
                ],
            ],
        ]);

        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($systemInstall);

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