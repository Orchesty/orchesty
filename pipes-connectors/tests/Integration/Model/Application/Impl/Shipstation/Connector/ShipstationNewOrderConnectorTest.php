<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shipstation\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector\ShipstationNewOrderConnector;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockCurlMethod;
use PHPUnit\Framework\Attributes\DataProvider as PhpunitDataProvider;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class ShipstationNewOrderConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shipstation\Connector
 */
final class ShipstationNewOrderConnectorTest extends KernelTestCaseAbstract
{

    public const string API_KEY    = '79620d3760d**********18f8a35dec8';
    public const string API_SECRET = '9cabe470**********751904f45f80e2';

    /**
     * @param int  $code
     * @param bool $isValid
     *
     * @throws Exception
     */
    #[PhpunitDataProvider('getDataProvider')]
    public function testProcessAction(int $code, bool $isValid): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    [],
                ),
            ],
        );

        $app = self::getContainer()->get('hbpf.application.shipstation');

        $applicationInstall = DataProvider::getBasicAppInstall(
            $app->getName(),
            self::API_KEY,
            self::API_SECRET,
        );

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shipstation"],"users":["79620d3760d**********18f8a35dec8"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shipstation"],"users":["79620d3760d**********18f8a35dec8"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $shipstationNewOrderConnector = new ShipstationNewOrderConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $shipstationNewOrderConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($app);

        $response = $shipstationNewOrderConnector->processAction(
            DataProvider::getProcessDto(
                $app->getName(),
                self::API_KEY,
                File::getContent(sprintf('%s/Data/newOrder.json', __DIR__)),
            ),
        );

        $responseNoUrl = $shipstationNewOrderConnector->processAction(
            DataProvider::getProcessDto(
                $app->getName(),
                self::API_KEY,
                File::getContent(sprintf('%s/Data/newOrderNoUrl.json', __DIR__)),
            ),
        );

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code),
            );
        } else {
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code),
            );
        }

        self::assertEquals(ProcessDtoAbstract::STOP_AND_FAILED, $responseNoUrl->getHeaders()['result-code']);
    }

    /**
     * @throws Exception
     */
    public function testGetName(): void
    {
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );

        self::assertSame(
            'shipstation_new_order',
            $shipstationNewOrderConnector->getName(),
        );
    }

    /**
     * @return mixed[]
     */
    public static function getDataProvider(): array
    {
        return [
            [404, FALSE],
            [200, TRUE],
        ];
    }

}
