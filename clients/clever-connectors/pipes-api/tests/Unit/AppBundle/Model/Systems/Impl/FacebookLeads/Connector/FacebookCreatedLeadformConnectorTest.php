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
use Clue\React\Buzz\Message\ResponseException;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @var MockObject|CurlSender
     */
    protected $sender;

    /**
     * @var MockObject|FacebookLeadsSystem
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
     * @var MockObject|SystemInstall
     */
    protected $systemInstall;

    /**
     * @var MockObject|DocumentManager
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

        $connector = new FacebookCreatedLeadformConnector($this->system, $this->lastSyncManager, $this->factory,
            $this->mockDm);

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
    public function testProcessBatchLimit(): void
    {
        $loop = Factory::create();

        $processDto = new ProcessDto();
        $processDto
            ->setHeaders([]);

        $this->initMocks();

        $this->sender
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function (RequestDto $requestDto) {
                $body = json_encode([
                    'error' => [
                        'code' => 4, // means: request limit reached
                    ],
                ]);

                return resolve(new ResponseException(new Response(400, [], $body)));
            });

        $connector = new FacebookCreatedLeadformConnector($this->system, $this->lastSyncManager, $this->factory,
            $this->mockDm);

        $data = $connector->processBatch($processDto, $loop, function (): void {
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
     *
     */
    private function initMocks(): void
    {

        $this->systemInstall = $this->createMock(SystemInstall::class);
        $this->systemInstall->method('getSettings')->willReturn([
            'form_id'      => '123456',
            'access_token' => '987654321',
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