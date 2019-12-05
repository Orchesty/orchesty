<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Hubspot\Mapper;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Mapper\HubspotCreateContactMapper;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector\ShipstationNewOrderConnector;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;
use Tests\MockCurlMethod;

/**
 * Class HubspotCreateContactMapperTest
 *
 * @package Tests\Integration\Model\Application\Impl\Hubspot\Mapper
 */
final class HubspotCreateContactMapperTest extends DatabaseTestCaseAbstract
{

    public const API_KEY = '3cc4771e-deb7-4905-8e6b-d2**********';
    public const API_SECRET = '5ef27043-34cc-43d1-9751-65**********';

    /**
     * @throws DateTimeException
     * @throws PipesFrameworkException
     * @throws CurlException
     * @throws ApplicationInstallException
     */
    public function testProcessAction(): void
    {
        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    'responseShipstation.json',
                    []
                ),
            ]
        );

        $shipstation                  = self::$container->get('hbpf.application.shipstation');
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector(
            self::$container->get('hbpf.transport.curl_manager'),
            $this->dm
        );

        $shipstationNewOrderConnector->setApplication($shipstation);

        $applicationInstall = DataProvider::getBasicAppInstall(
            $shipstation->getKey(),
            self::API_KEY,
            self::API_SECRET
        );

        $this->pf($applicationInstall);

        $response = $shipstationNewOrderConnector->processEvent(
            DataProvider::getProcessDto(
                $shipstation->getKey(),
                self::API_KEY,
                (string) file_get_contents(sprintf('%s/Data/newOrderShipstation.json', __DIR__), TRUE)
            )
        );

        $response->setData((string) file_get_contents(sprintf('%s/Data/responseShipstation.json', __DIR__), TRUE));

        $hubspotCreateContactMapper = new HubspotCreateContactMapper();
        $dto                        = $hubspotCreateContactMapper->process($response);

        self::assertEquals(
            Json::decode($dto->getData()),
            Json::decode(
                (string) file_get_contents(__DIR__ . sprintf('/Data/requestHubspot.json'), TRUE)
            )
        );

    }

}
