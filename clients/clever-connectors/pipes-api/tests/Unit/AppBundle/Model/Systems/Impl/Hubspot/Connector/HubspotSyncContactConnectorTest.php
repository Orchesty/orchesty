<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector\HubspotSyncContactConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\CurlManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Class HubspotSyncContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
final class HubspotSyncContactConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testProcessBatch(): void
    {
        $dtoData = [
            'data' => [
                'system_install' => ['user' => '123'],
            ],
        ];

        $headers[CMHeaders::createKey(CMHeaders::GUID)]       = 'user123';
        $headers[CMHeaders::createKey(CMHeaders::TOKEN)]      = 'token123';
        $headers[CMHeaders::createKey(CMHeaders::SYSTEM_KEY)] = 'system123';
        $headers[CMHeaders::createKey(CMHeaders::PROCESS_ID)] = '123';

        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders($headers)
            ->setData(json_encode($dtoData));

        /** @var HubspotSyncContactConnector $syncConn */
        $syncConn = $this->mockSync();
        $data     = $syncConn->processBatch($processDto, $loop, function (): void {
        });

        $data->then(
            function (): void {
                $this->assertTrue(TRUE);
            }
        )->done();

        $loop->run();
    }

    /**
     *
     */
    public function testProcessBatchLimit(): void
    {
        $dtoData = [
            'data' => [
                'system_install' => ['user' => '123'],
            ],
        ];

        $headers[CMHeaders::createKey(CMHeaders::GUID)]       = 'user123';
        $headers[CMHeaders::createKey(CMHeaders::TOKEN)]      = 'token123';
        $headers[CMHeaders::createKey(CMHeaders::SYSTEM_KEY)] = 'system123';
        $headers[CMHeaders::createKey(CMHeaders::PROCESS_ID)] = '123';

        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders($headers)
            ->setData(json_encode($dtoData));

        /** @var HubspotSyncContactConnector $syncConn */
        $syncConn = $this->mockSync(TRUE);
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
     * @param bool $limit
     *
     * @return MockObject|HubspotSyncContactConnector
     */
    private function mockSync($limit = FALSE)
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('setSyncTime')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->method('getRepository')
            ->willReturn($systemInstall);

        /** @var MockObject|CurlManagerInterface $sender */
        $sender = $this->createMock(CurlSender::class);

        /** @var MockObject|CurlSenderFactory $factory */
        $factory = $this->createMock(CurlSenderFactory::class);
        $factory
            ->method('create')
            ->willReturn($sender);

        $progressCounter = $this->createMock(ProgressCounterService::class);
        $progressCounter->method('setTotal')->willReturn(TRUE);

        $syncConn = $this->getMockBuilder(HubspotSyncContactConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $dm, $factory, $progressCounter])
            ->getMock();

        $contacts = [
            'contacts'   => [
                0 => ['vid' => 123],
            ],
            'has-more'   => FALSE,
            'vid-offset' => 123,
        ];

        if ($limit) {
            $syncConn->expects($this->at(0))
                ->method('fetchData')
                ->willReturn(reject(new Response(429)));
        } else {
            $syncConn->expects($this->at(0))
                ->method('fetchData')
                ->willReturn(resolve(new Response(200, [], json_encode($contacts))));
        }

        return $syncConn;
    }

    /**
     * @return MockObject|HubspotSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('https://api.hubapi.com/'));
        $requestDto->setHeaders([
            'X-Hubspot-Access-Token' => 'token123',
            'Content-Type'           => 'application/json',
        ]);
        $mock = $this->createMock(HubspotSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}