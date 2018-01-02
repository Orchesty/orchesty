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
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
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
     * @return PHPUnit_Framework_MockObject_MockObject|AirtableSyncContactConnector
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

        $syncConn = $this->getMockBuilder(AirtableSyncContactConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $lastSync, $sender, $dm, $processCounter])
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