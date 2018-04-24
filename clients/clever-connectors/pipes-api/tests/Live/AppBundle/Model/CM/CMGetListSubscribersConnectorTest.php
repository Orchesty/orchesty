<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMGetListSubscribersConnector;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Clue\React\Buzz\Browser;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Metrics\InfluxDbSender;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use PHPUnit_Framework_MockObject_MockObject;
use React\EventLoop\Factory;
use React\Socket\Connector;
use React\Socket\SecureConnector;
use Tests\KernelTestCaseAbstract;

/**
 * Class CMGetListSubscribersConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\CM
 */
final class CMGetListSubscribersConnectorTest extends KernelTestCaseAbstract
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

        $context = [
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

        $systemInstall = new SystemInstall();
        $systemInstall
            ->setUser('5a8b121f-a74c-11e7-a177-000d3a20eb16')
            ->setToken('+-cl2-3FR-6FD_83L+_19X6+hbZrtfeI')
            ->setSettings([
                SystemInstall::DISTRIBUTION_LIST => '0b8cc606-2991-67d5-6e95-4feb3731c615',
            ]);

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo
            ->expects($this->at(0))
            ->method('getSystemInstallFromHeaders')
            ->willReturn($systemInstall);

        $dm = $this->createMock(DocumentManager::class);
        $dm
            ->expects($this->at(0))
            ->method('getRepository')
            ->willReturn($repo);

        $conn = new CMGetListSubscribersConnector($dm, $factory, $progressCounter, ['cert' => '']);

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