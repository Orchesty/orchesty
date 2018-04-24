<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Shoptet\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\Connector\ShoptetSyncCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shoptet\ShoptetSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class ShoptetSyncCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Shoptet\Connector
 */
final class ShoptetSyncCustomerConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers ShoptetSyncCustomerConnector::processBatch()
     */
    public function testProcessBatch(): void
    {
        $loop = Factory::create();

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode(['data' => ['system_install' => []], ['settings' => [], 'user' => '123']]));

        /** @var ShoptetSyncCustomerConnector $syncConn */
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
     * @return MockObject|ShoptetSyncCustomerConnector
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

        $sender = $this->createMock(CurlSenderFactory::class);

        $syncConn = $this->getMockBuilder(ShoptetSyncCustomerConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$this->mockSystem(), $sender, $dm])
            ->getMock();

        $syncConn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], '<xml>')));

        return $syncConn;
    }

    /**
     * @return MockObject|ShoptetSystem
     */
    private function mockSystem()
    {
        $requestDto = new RequestDto('GET',
            new Uri('https://179974.myshoptet.com/export/customers.xml?ip=188.122.212.69&hash=31c6bc83857bb10328887befd0f41d4f81e6cf6a88ee644aa77e250c4d9efbd6'));
        $requestDto->setHeaders([
            'Content-Type' => 'text/xml',
            'Accept'       => 'text/xml',
        ]);
        $mock = $this->createMock(ShoptetSystem::class);
        $mock->method('getRequestDto')->willReturn($requestDto);

        return $mock;
    }

}