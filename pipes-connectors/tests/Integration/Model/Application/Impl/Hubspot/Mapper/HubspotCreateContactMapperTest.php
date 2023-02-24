<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot\Mapper;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Mapper\HubSpotCreateContactMapper;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Shipstation\Connector\ShipstationNewOrderConnector;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use HbPFConnectorsTests\MockCurlMethod;
use HbPFConnectorsTests\MockServer\Mock;
use HbPFConnectorsTests\MockServer\MockServer;

/**
 * Class HubspotCreateContactMapperTest
 *
 * @package HbPFConnectorsTests\Integration\Model\Application\Impl\Hubspot\Mapper
 */
final class HubspotCreateContactMapperTest extends KernelTestCaseAbstract
{

    public const API_KEY    = '3cc4771e-deb7-4905-8e6b-d2**********';
    public const API_SECRET = '5ef27043-34cc-43d1-9751-65**********';

    /**
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $this->mockCurl(
            [
                new MockCurlMethod(
                    200,
                    'responseShipstation.json',
                    [],
                ),
            ],
        );

        $shipstation                  = self::getContainer()->get('hbpf.application.shipstation');
        $shipstationNewOrderConnector = new ShipstationNewOrderConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $shipstationNewOrderConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($shipstation);

        $applicationInstall = DataProvider::getBasicAppInstall(
            $shipstation->getName(),
            self::API_KEY,
            self::API_SECRET,
        );

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shipstation"],"users":["3cc4771e-deb7-4905-8e6b-d2**********"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["shipstation"],"users":["3cc4771e-deb7-4905-8e6b-d2**********"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode([$applicationInstall->toArray()])),
            ),
        );

        $response = $shipstationNewOrderConnector->processAction(
            DataProvider::getProcessDto(
                $shipstation->getName(),
                self::API_KEY,
                File::getContent(sprintf('%s/Data/newOrderShipstation.json', __DIR__)),
            ),
        );

        $responseNoBody = $shipstationNewOrderConnector->processAction(
            DataProvider::getProcessDto(
                $shipstation->getName(),
                self::API_KEY,
            ),
        );

        $response->setData(File::getContent(sprintf('%s/Data/responseShipstation.json', __DIR__)));

        $hubspotCreateContactMapper = new HubSpotCreateContactMapper(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $dto                        = $hubspotCreateContactMapper->processAction($response);
        $dtoNoBody                  = $hubspotCreateContactMapper->processAction($responseNoBody);

        self::assertEquals(
            Json::decode($dto->getData()),
            Json::decode(File::getContent(__DIR__ . '/Data/requestHubspot.json')),
        );

        self::assertEquals(ProcessDtoAbstract::STOP_AND_FAILED, $dtoNoBody->getHeaders()['result-code']);
    }

}
