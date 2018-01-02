<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMGetSubscribersConnector;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use Clue\React\Buzz\Browser;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use React\Socket\Connector;
use React\Socket\SecureConnector;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMGetSubscribersConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\CM
 */
final class CMGetSubscribersConnectorTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testBatchAction(): void
    {
        $this->markTestSkipped('Online test');

        $loop = Factory::create();
        $dto  = (new ProcessDto())->setHeaders([
            'pf-guid'       => '5a8b121f-a74c-11e7-a177-000d3a20eb16',
            'pf-token'      => '+-cl2-3FR-6FD_83L+_19X6+hbZrtfeI',
            'pf-system-key' => 'facebookaudience',
        ]);

        $context    = [
            'verify_peer'      => FALSE,
            'verify_peer_name' => FALSE,
        ];

        /** @var PHPUnit_Framework_MockObject_MockObject|InfluxDbSender $influxDbSender */
        $influxDbSender = $this->createMock(InfluxDbSender::class);
        $influxDbSender
            ->method('send')
            ->willReturn(TRUE);

        $browser    = new Browser($loop, new SecureConnector(new Connector($loop), $loop, $context));
        $curlSender = new CurlSender($browser, $influxDbSender);
        $curlSender->setLogger($this->container->get('monolog.logger.commons'));

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlSenderFactory $factory */
        $factory = $this->createMock(CurlSenderFactory::class);
        $factory
            ->expects($this->at(0))
            ->method('create')
            ->willReturn($curlSender);

        /** @var PHPUnit_Framework_MockObject_MockObject|ProgressCounterService $progressCounter */
        $progressCounter = $this->createMock(ProgressCounterService::class);
        $progressCounter
            ->method('setTotal')
            ->willReturn(NULL);

        $conn = new CMGetSubscribersConnector($this->dm, $factory, $progressCounter, ['ca' => '', 'cert' => '']);

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