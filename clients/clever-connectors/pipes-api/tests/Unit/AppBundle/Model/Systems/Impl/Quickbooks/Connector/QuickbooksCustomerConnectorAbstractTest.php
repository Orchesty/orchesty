<?php

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector;


use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksCreateCustomerConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksCustomerConnectorAbstract;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\QuickbooksSystem;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use function React\Promise\resolve;
use Tests\ConnectorTestCaseAbstract;


/**
 * Class QuickbooksCreateCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
abstract class QuickbooksCustomerConnectorAbstractTest extends ConnectorTestCaseAbstract
{

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|SystemInstall
     */
    protected $systemInstall;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|QuickbooksSystem
     */
    protected $system;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|LastSyncManager
     */
    protected $lastSyncManager;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|CurlSenderFactory
     */
    protected $factory;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|CurlSender
     */
    protected $sender;

    /**
     * @covers ::processBatch()
     *
     * @return void
     */
    public function testProcessBatch(): void
    {
        /** @var SuccessMessage $result */
        $result = null;
        $this->initMocks();

        $promiseCount = resolve(new Response(200, [], $this->getRequest('QuickbooksCountCustomerResponse.json')));
        $promiseData = resolve(new Response(200, [], $this->getRequest('QuickbooksCustomerResponse.json')));

        $this->sender->expects($this->at(0))->method('send')->willReturn($promiseCount);
        $this->sender->expects($this->at(1))->method('send')->willReturn($promiseData);

        $connector = $this->createConnector();

        $dtoData = [
            'system_install' => ['user' => '123'],
            'topology'       => ['name' => 'top'],
        ];

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([])
            ->setData(json_encode($dtoData));

        $loop       = Factory::create();

        $connector->processBatch($processDto, $loop, function (SuccessMessage $data) use (&$result): void {
            $result = $data;
        })->done();

        $loop->run();

        $this->assertInstanceOf(SuccessMessage::class, $result);
        $data = json_decode($result->getData());
        $this->assertTrue(is_array($data));
        $this->assertCount(3, $data);
        $this->assertEquals(1, $result->getSequenceId());
    }

    /**
     * @return void
     */
    protected function initMocks(): void
    {
        $this->systemInstall = $this->createMock(SystemInstallRepository::class);
        $this->systemInstall->method('setSyncTime')->willReturn(NULL);

        $this->dm = $this->createMock(DocumentManager::class);
        $this->dm
            ->method('getRepository')
            ->willReturn($this->systemInstall);

        $requestDto = new RequestDto('GET', new Uri('http://test.neco'));
        $requestDto->setHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . '<access_token>',
        ]);

        $this->system = $this->createMock(QuickbooksSystem::class);
        $this->system->method('getRequestDto')->willReturn($requestDto);

        $this->lastSyncManager = $this->createMock(LastSyncManager::class);
        $this->sender = $this->createMock(CurlSender::class);
        $this->factory = $this->createMock(CurlSenderFactory::class);
        $this->factory->method('create')->willReturn($this->sender);
    }


    abstract protected function createConnector(): QuickbooksCustomerConnectorAbstract;
}