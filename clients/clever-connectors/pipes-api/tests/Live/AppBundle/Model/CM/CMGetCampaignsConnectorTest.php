<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\CM;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\CM\CampaignConnector\CMGetCampaignsConnector;
use Clue\React\Buzz\Browser;
use Exception;
use Hanaboso\PipesFramework\Commons\Crypt\CryptManager;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSender;
use Hanaboso\PipesFramework\Commons\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\PipesFramework\RabbitMq\Impl\Batch\SuccessMessage;
use Nette\Utils\Json;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use React\Socket\Connector;
use React\Socket\SecureConnector;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class CMGetCampaignsConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\CM
 */
final class CMGetCampaignsConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testBatchAction(): void
    {
        $this->markTestSkipped('Online test');

        $loop = Factory::create();
        $dto  = (new ProcessDto())->setHeaders([
            'pf-guid'       => '5a8b121f-a74c-11e7-a177-000d3a20eb16',
            'pf-token'      => '+-cl2-3FR-6FD_83L+_19X6+hbZrtfeI',
            'pf-system-key' => 'salesforceapp',
        ]);

        $context = [
            'verify_peer'      => FALSE,
            'verify_peer_name' => FALSE,
        ];

        /** @var MockObject|InfluxDbSender $influxDbSender */
        $influxDbSender = $this->createMock(InfluxDbSender::class);
        $influxDbSender
            ->method('send')
            ->willReturn(TRUE);

        $browser    = new Browser($loop, new SecureConnector(new Connector($loop), $loop, $context));
        $curlSender = new CurlSender($browser, $influxDbSender);
        $curlSender->setLogger($this->container->get('monolog.logger.commons'));

        /** @var MockObject|CurlSenderFactory $factory */
        $factory = $this->createMock(CurlSenderFactory::class);
        $factory
            ->expects($this->at(0))
            ->method('create')
            ->willReturn($curlSender);

        $system = new SystemInstall();
        $system
            ->setUser('5a8b121f-a74c-11e7-a177-000d3a20eb16')
            ->setToken('+-cl2-3FR-6FD_83L+_19X6+hbZrtfeI')
            ->setSystem('salesforceapp')
            ->setSettings([]);

        $this->persistAndFlush($system);

        $dtoData = [
            'system_install' => [
                '_id'               => $system->getId(),
                'user'              => $system->getUser(),
                'token'             => $system->getToken(),
                'system'            => $system->getSystem(),
                'encryptedSettings' => CryptManager::encrypt([]),
            ],
        ];

        $dto->setData(Json::encode($dtoData));

        $conn = new CMGetCampaignsConnector($factory, ['cert' => '']);

        $process = $conn->processBatch($dto, $loop, function (SuccessMessage $message): void {
            $this->assertTrue(is_array(Json::decode($message->getData(), TRUE)));
        });

        $process->then(
            function (): void {
                $this->assertTrue(TRUE);
            },
            function (): void {
                $this->assertTrue(FALSE);
            }
        )->done();

        $loop->run();
    }

}