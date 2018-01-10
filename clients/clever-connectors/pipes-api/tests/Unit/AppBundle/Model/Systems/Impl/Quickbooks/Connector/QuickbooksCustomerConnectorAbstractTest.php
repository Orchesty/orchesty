<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
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
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class QuickbooksCreateCustomerConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector
 */
abstract class QuickbooksCustomerConnectorAbstractTest extends ConnectorTestCaseAbstract
{

    /**
     * @var MockObject|SystemInstall
     */
    protected $systemInstall;

    /**
     * @var MockObject|QuickbooksSystem
     */
    protected $system;

    /**
     * @var MockObject|LastSyncManager
     */
    protected $lastSyncManager;

    /**
     * @var MockObject|CurlSenderFactory
     */
    protected $factory;

    /**
     * @var MockObject|CurlSender
     */
    protected $sender;

    /**
     * @var MockObject|DocumentManager
     */
    protected $mockDm;

    /**
     * @var MockObject|ProgressCounterService
     */
    protected $counterService;

    /**
     * @covers ::processBatch()
     *
     * @return void
     */
    public function testProcessBatch(): void
    {
        /** @var SuccessMessage $result */
        $result = NULL;
        $this->initMocks();

        $promiseCount = resolve(new Response(200, [], $this->getRequest('QuickbooksCountCustomerResponse.json')));
        $promiseData  = resolve(new Response(200, [], $this->getRequest('QuickbooksCustomerResponse.json')));

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

        $loop = Factory::create();

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

        $this->mockDm = $this->createMock(DocumentManager::class);
        $this->mockDm
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
        $this->sender          = $this->createMock(CurlSender::class);
        $this->factory         = $this->createMock(CurlSenderFactory::class);
        $this->factory->method('create')->willReturn($this->sender);

        $this->counterService = $this->createMock(ProgressCounterService::class);
        $this->counterService->method('setTotal')->willReturn(TRUE);
    }

    /**
     * @return QuickbooksCustomerConnectorAbstract
     */
    abstract protected function createConnector(): QuickbooksCustomerConnectorAbstract;

}