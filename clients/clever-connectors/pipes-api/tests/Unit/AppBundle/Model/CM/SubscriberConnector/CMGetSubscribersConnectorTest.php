<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMGetSubscribersConnector;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\KernelTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class CMGetSubscribersConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\CM\SubscriberConnector
 */
final class CMGetSubscribersConnectorTest extends KernelTestCaseAbstract
{

    /**
     * @covers CMGetSubscribersConnector::processBatch()
     */
    public function testProcessBatch(): void
    {
        $loop = Factory::create();
        $dto  = (new ProcessDto())->setHeaders([
            'pf-guid'       => '51a83cfe-9e04-11e7-a177-000d3a20eb16',
            'pf-token'      => '-3*QYg*3H-5+vaez_K7_N-4K1YhCn88k',
            'pf-system-key' => 'neco',
        ]);

        $curlSender = $this->createMock(CurlSender::class);

        /** @var MockObject|CurlSenderFactory $factory */
        $factory = $this->createMock(CurlSenderFactory::class);
        $factory
            ->expects($this->at(0))
            ->method('create')
            ->willReturn($curlSender);

        $dm = $this->createMock(DocumentManager::class);

        /** @var MockObject|CMGetSubscribersConnector $conn */
        $conn = $this->getMockBuilder(CMGetSubscribersConnector::class)
            ->setMethods(['fetchData'])
            ->setConstructorArgs([$dm, $factory, ['ca' => '', 'cert' => '']])
            ->getMock();

        $conn->expects($this->at(0))
            ->method('fetchData')
            ->willReturn(resolve(new Response(200, [], json_encode([['email' => 'aa@aa.com']]))));

        $conn->expects($this->at(1))
            ->method('fetchData')
            ->willReturn(resolve(new Response(204, [], json_encode([['email' => 'aa@aa.com']]))));

        $process = $conn->processBatch($dto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));
        });

        $process->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function ($data): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
    }

}