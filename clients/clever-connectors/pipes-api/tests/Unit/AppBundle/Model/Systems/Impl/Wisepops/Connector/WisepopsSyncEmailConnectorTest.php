<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\Connector;

use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector\ShopifySyncCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\Connector\WisepopsSyncEmailConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Wisepops\WisepopsSystem;
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
 * Class WisepopsSyncEmailConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Wisepops\Connector
 */
final class WisepopsSyncEmailConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers WisepopsSyncEmailConnector::processBatch()
     * @covers WisepopsSyncEmailConnector::getPage()
     */
    public function testProcessBatch(): void
    {
        $dtoData = [
            'data' => [
                'system_install' => ['user' => '123'],
                'topology'       => ['name' => 'top'],
            ],
        ];

        $loop       = Factory::create();
        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        /** @var WisepopsSyncEmailConnector $syncConn */
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
     * @return PHPUnit_Framework_MockObject_MockObject|ShopifySyncCustomerConnector
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

        $processCounter = $this->createMock(ProgressCounterService::class);
        $processCounter->method('setTotal')->willReturn(TRUE);

        $syncConn = $this->getMockBuilder(WisepopsSyncEmailConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $dm, $sender, $processCounter])
            ->getMock();

        $syncConn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode([['email' => 'eml']]))));

        return $syncConn;
    }

    /**
     * @return PHPUnit_Framework_MockObject_MockObject|WisepopsSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET', new Uri('http://wisepops.com/'));
        $requestDto->setHeaders([
            'Authorization' => 'WISEPOPS-API key="$2y$10$W4bsH4haTHOk04Oip9seTuvDcrcbdPxwtZDZwaWZQkLyuCfXNnwu6"',
            'Content-Type'  => 'application/json',
        ]);
        $mock = $this->createMock(WisepopsSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}