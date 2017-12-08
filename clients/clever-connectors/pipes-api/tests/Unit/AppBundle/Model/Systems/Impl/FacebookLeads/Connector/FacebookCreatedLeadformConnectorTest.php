<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 12/7/17
 * Time: 1:37 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\FacebookLeads\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\LastSync\LastSyncManager;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\Connector\FacebookCreatedLeadformConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\FacebookLeads\FacebookLeadsSystem;
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
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class FacebookCreatedLeadformConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\FacebookLeads\Connector
 */
class FacebookCreatedLeadformConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|CurlSender
     */
    protected $sender;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|FacebookLeadsSystem
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
     * @var PHPUnit_Framework_MockObject_MockObject|SystemInstall
     */
    protected $systemInstall;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject|DocumentManager
     */
    private $mockDm;

    /**
     *
     */
    public function testProcessBatch(): void
    {
        /** @var SuccessMessage $result */
        $result = NULL;
        $this->initMocks();
        $promiseData = resolve(new Response(200, [], $this->getRequest('FacebookLeadformResponse.json')));

        $this->sender->expects($this->at(0))->method('send')->willReturn($promiseData);

        $connector = new FacebookCreatedLeadformConnector($this->system, $this->lastSyncManager, $this->factory, $this->mockDm);

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([]);

        $loop = Factory::create();

        $connector->processBatch($processDto, $loop, function (SuccessMessage $data) use (&$result): void {
            $result = $data;
        })->done();

        $loop->run();
        $this->assertInstanceOf(SuccessMessage::class, $result);
        $data = json_decode($result->getData());
        $this->assertTrue(is_array($data));
        $this->assertCount(2, $data);
        $this->assertEquals(0, $result->getSequenceId());
    }

    /**
     *
     */
    private function initMocks(): void
    {

        $this->systemInstall = $this->createMock(SystemInstall::class);
        $this->systemInstall->method('getSettings')->willReturn([
            'form_id' => '123456',
            'page_access_token' => '987654321',
        ]);

        $this->systemInstallRepository = $this->createMock(SystemInstallRepository::class);
        $this->systemInstallRepository->method('getSystemInstallFromHeaders')->willReturn($this->systemInstall);
        $this->systemInstallRepository->method('setSyncTime')->willReturn(NULL);

        $this->mockDm = $this->createMock(DocumentManager::class);
        $this->mockDm
            ->method('getRepository')
            ->willReturn($this->systemInstallRepository);

        $requestDto = new RequestDto('GET', new Uri('http://test.neco'));
        $requestDto->setHeaders([
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ]);

        $this->system = $this->createMock(FacebookLeadsSystem::class);
        $this->system->method('getRequestDto')->willReturn($requestDto);

        $this->lastSyncManager = $this->createMock(LastSyncManager::class);
        $this->sender          = $this->createMock(CurlSender::class);
        $this->factory         = $this->createMock(CurlSenderFactory::class);
        $this->factory->method('create')->willReturn($this->sender);
    }

}