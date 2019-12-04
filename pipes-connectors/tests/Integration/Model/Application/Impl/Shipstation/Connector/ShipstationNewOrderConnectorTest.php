<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Shipstation\Connector;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector\ShipstationNewOrderConnector;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class ShipstationNewOrderConnectorTest
 *
 * @package Tests\Integration\Model\Application\Impl\Shipstation\Connector
 */
final class ShipstationNewOrderConnectorTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = '8919bb213aab47b48f7bb07f1ce1e25c';
    public const API_SECRET = '996ab3153f154499a38221d22375424b';

    /**
     * @param int  $code
     * @param bool $isValid
     *
     * @throws ApplicationInstallException
     * @throws CurlException
     * @throws DateTimeException
     * @throws PipesFrameworkException
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
                    []
                ),
            ]
        );

        $app                          = self::$container->get('hbpf.application.shipstation');
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $shipstationNewOrderConnector->setApplication($app);

        $applicationInstall = DataProvider::getBasicAppInstall(
            $app->getKey(),
            self::API_KEY,
            self::API_SECRET
        );

        $this->pf($applicationInstall);
        $response = $shipstationNewOrderConnector->processEvent(
            DataProvider::getProcessDto(
                $app->getKey(),
                self::API_KEY,
                (string) file_get_contents(sprintf('%s/Data/newOrder.json', __DIR__), TRUE)
            )
        );

        $responseNoUrl = $shipstationNewOrderConnector->processEvent(
            DataProvider::getProcessDto(
                $app->getKey(),
                self::API_KEY,
                (string) file_get_contents(sprintf('%s/Data/newOrderNoUrl.json', __DIR__), TRUE)
            )
        );

        if ($isValid) {
            self::assertSuccessProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        } else {
            self::assertFailedProcessResponse(
                $response,
                sprintf('response%s.json', $code)
            );
        }

        self::assertEquals($responseNoUrl->getHeaders()['pf-result-code'], ProcessDto::STOP_AND_FAILED);

    }

    /**
     * @return array
     */
    public function getDataProvider(): array
    {
        return [
            [404, FALSE],
            [200, TRUE],
        ];
    }

}

