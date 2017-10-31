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
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class HubspotSyncContactConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
final class HubspotSyncContactConnectorTest extends KernelTestCaseAbstract
{

    /**
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
     * @return PHPUnit_Framework_MockObject_MockObject|HubspotSyncContactConnector
     */
    private function mockSync()
    {
        $systemInstall = $this->createMock(SystemInstallRepository::class);
        $systemInstall->method('setSyncTime')->willReturn(NULL);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->method('getRepository')
            ->willReturn($systemInstall);

        $sender = $this->createMock(CurlSenderFactory::class);

        $progressCounter = $this->createMock(ProgressCounterService::class);
        $progressCounter->method('setTotal')->willReturn(TRUE);

        $syncConn = $this->getMockBuilder(HubspotSyncContactConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $dm, $sender, $progressCounter])
            ->getMock();

        $contacts = [
            'contacts'   => [
                0 => ['vid' => 123],
            ],
            'has-more'   => FALSE,
            'vid-offset' => 123,
        ];

        $syncConn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode($contacts))));

        return $syncConn;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|HubspotSystem
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