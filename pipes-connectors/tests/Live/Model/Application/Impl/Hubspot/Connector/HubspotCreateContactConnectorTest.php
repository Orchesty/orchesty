<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Hubspot\Connector;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector;
use Hanaboso\PhpCheckUtils\PhpUnit\Traits\CustomAssertTrait;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Json;
use HbPFConnectorsTests\DataProvider;
use HbPFConnectorsTests\KernelTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class HubspotCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Hubspot\Connector
 */
final class HubspotCreateContactConnectorTest extends KernelTestCaseAbstract
{

    use CustomAssertTrait;

    /**
     * @group live
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $mockServer);

        $app = self::getContainer()->get('hbpf.application.hub-spot');
        $mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["hub-spot"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(200, [], Json::encode(
                    DataProvider::getOauth2AppInstall(
                        $app->getName(),
                        'user',
                        'CPn036zALRICAQEYu_j2AiDpsIEEKJSQDDIZAAoa8Oq06qseob1dXiP5KB1H3dY5AG0ShToPAAoCQQAADIADAAgAAAABQhkAChrw6vXLFCILvuPoTaMFCHwh43lT3Ura',
                    )->toArray(),
                )),
            ),
        );

        $hubspotCreateContactConnector = new HubSpotCreateContactConnector(
            self::getContainer()->get('hbpf.application_install.repository'),
        );
        $hubspotCreateContactConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setApplication($app);

        $hubspotCreateContactConnector->processAction(
            DataProvider::getProcessDto(
                $app->getName(),
                'user',
                File::getContent(__DIR__ . '/Data/contactBody.json'),
            ),
        );

        self::assertFake();
    }

}
