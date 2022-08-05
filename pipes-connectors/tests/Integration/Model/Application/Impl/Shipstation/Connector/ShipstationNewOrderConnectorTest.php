<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Shipstation\Connector;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector\ShipstationNewOrderConnector;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\MockCurlMethod;

/**
 * Class ShipstationNewOrderConnectorTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Shipstation\Connector
 */
final class ShipstationNewOrderConnectorTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = '79620d3760d**********18f8a35dec8';
    public const API_SECRET = '9cabe470**********751904f45f80e2';

    /**
     * @param int  $code
     * @param bool $isValid
     *
     * @throws Exception
     *
     * @dataProvider getDataProvider
     */
    public function testProcessAction(int $code, bool $isValid): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    $code,
                    sprintf('response%s.json', $code),
                    [],
                ),
            ],
        );

        $app                          = self::getContainer()->get('hbpf.application.shipstation');
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector();
        $shipstationNewOrderConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setDb($this->dm)
            ->setApplication($app);

        $applicationInstall = DataProvider::getBasicAppInstall(
            $app->getName(),
            self::API_KEY,
            self::API_SECRET,
        );

        $this->pfd($applicationInstall);
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
     * @return mixed[]
     */
    public function getDataProvider(): array
    {
        return [
            [404, FALSE],
            [200, TRUE],
        ];
    }

    /**
     *
     */
    public function testGetName(): void
    {
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector();

        self::assertEquals(
            'shipstation_new_order',
            $shipstationNewOrderConnector->getName(),
        );
    }

}
