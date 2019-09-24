<?php declare(strict_types=1);

namespace Tests\Integration\Model\Application\Impl\Hubspot\Mapper;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\CommonsBundle\Exception\PipesFrameworkException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Mapper\HubspotCreateContactMapper;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector\ShipstationNewOrderConnector;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Tests\DatabaseTestCaseAbstract;
use Tests\DataProvider;

/**
 * Class HubspotCreateContactMapperTest
 *
 * @package Tests\Integration\Model\Application\Impl\Hubspot\Connector
 */
final class HubspotCreateContactMapperTest extends DatabaseTestCaseAbstract
{

    public const API_KEY    = '8919bb213aab47b48f7bb07f1ce1e25c';
    public const API_SECRET = '996ab3153f154499a38221d22375424b';

    /**
     * @throws DateTimeException
     * @throws PipesFrameworkException
     * @throws CurlException
     * @throws ApplicationInstallException
     */
    public function testProcessAction(): void
    {

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

        $response = $shipstationNewOrderConnector->processEvent(DataProvider::getProcessDto(
            $shipstation->getKey(),
            self::API_KEY,
            (string) file_get_contents(sprintf('%s/Data/newOrderShipstation.json', __DIR__), TRUE)
        ));

        $response->setData((string) file_get_contents(sprintf('%s/Data/responseShipstation.json', __DIR__), TRUE));

        $hubspotCreateContactMapper = new HubspotCreateContactMapper();
        $dto                        = $hubspotCreateContactMapper->process($response);

        self::assertEquals(
            json_decode($dto->getData(), TRUE, 512, JSON_THROW_ON_ERROR),
            json_decode(
                (string) file_get_contents(__DIR__ . sprintf('/Data/requestHubspot.json'), TRUE),
                TRUE,
                512,
                JSON_THROW_ON_ERROR
            )
        );

    }

}
