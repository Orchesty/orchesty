<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMGetSubscribersConnector;
use Clue\React\Buzz\Browser;
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
            'pf-guid'       => '51a83cfe-9e04-11e7-a177-000d3a20eb16',
            'pf-token'      => '-3*QYg*3H-5+vaez_K7_N-4K1YhCn88k',
            'pf-system-key' => 'neco',
        ]);

        $context    = [
            'verify_peer'      => FALSE,
            'verify_peer_name' => FALSE,
        ];
        $browser    = new Browser($loop, new SecureConnector(new Connector($loop), $loop, $context));
        $curlSender = new CurlSender($browser);
        $curlSender->setLogger($this->container->get('monolog.logger.commons'));

        /** @var PHPUnit_Framework_MockObject_MockObject|CurlSenderFactory $factory */
        $factory = $this->createMock(CurlSenderFactory::class);
        $factory
            ->expects($this->at(0))
            ->method('create')
            ->willReturn($curlSender);

        $conn = new CMGetSubscribersConnector($this->dm, $factory, ['ca' => '', 'cert' => '']);

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