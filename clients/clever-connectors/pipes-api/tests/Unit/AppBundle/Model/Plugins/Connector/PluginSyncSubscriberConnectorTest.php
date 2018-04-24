<?php declare(strict_types=1);

namespace Tests\Unit\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Plugins\Connector\PluginSyncSubscriberConnector;
use CleverConnectors\AppBundle\Model\ProgressCounter\ProgressCounterService;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSender;
use Hanaboso\CommonsBundle\Transport\AsyncCurl\CurlSenderFactory;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use PHPUnit\Framework\MockObject\MockObject;
use React\EventLoop\Factory;
use Tests\ConnectorTestCaseAbstract;
use function React\Promise\resolve;

/**
 * Class PluginSyncSubscriberConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Plugins\Connector
 */
final class PluginSyncSubscriberConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testConnector(): void
    {
        /** @var ProgressCounterService|MockObject $counter */
        $counter = $this->createMock(ProgressCounterService::class);
        $counter->method('setTotal')->willReturn('');

        $conn = new PluginSyncSubscriberConnector(
            $this->mockDm(),
            $this->mockCurl(),
            $counter,
            $this->container->get('cc.systems.loader')
        );

        $loop = Factory::create();

        $dto = new ProcessDto();
        $dto->setHeaders([])->setData('');

        $data = $conn->processBatch($dto, $loop, function (): void {
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
     * @return DocumentManager|MockObject
     */
    private function mockDm()
    {
        $sys = new SystemInstall();
        $sys->setSettings([
            SystemInstall::SYSTEM_URL => 'https://aso.ko',
        ])->setSystem('null.user.group');

        $repo = $this->createMock(SystemInstallRepository::class);
        $repo->expects($this->once())
            ->method('getSystemInstallFromHeaders')->willReturn($sys);

        $dm = $this->createMock(DocumentManager::class);
        $dm->expects($this->once())
            ->method('getRepository')->willReturn($repo);

        return $dm;
    }

    /**
     * @return CurlSenderFactory|MockObject
     */
    private function mockCurl()
    {
        $sender = $this->createMock(CurlSender::class);
        $sender->expects($this->at(0))
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $dto = new RequestDto('GET',
                        new Uri('https://aso.ko/clever_connector/subscriber?page=1&limit=50'));

                    self::assertEquals($dto, $requestDto);

                    return resolve(new Response(200, [], $this->getRequest('syncPage.json')));
                }
            ));
        $sender->expects($this->at(1))
            ->method('send')->will($this->returnCallback(
                function (RequestDto $requestDto) {
                    $dto = new RequestDto('GET',
                        new Uri('https://aso.ko/clever_connector/subscriber?page=2&limit=50'));

                    self::assertEquals($dto, $requestDto);

                    return resolve(new Response(200, [], $this->getRequest('syncPage.json')));
                }
            ));

        /** @var CurlSenderFactory|MockObject $curl */
        $curl = $this->createMock(CurlSenderFactory::class);
        $curl->expects($this->once())
            ->method('create')->willReturn($sender);

        return $curl;
    }

}