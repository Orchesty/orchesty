<?php declare(strict_types=1);

namespace HbPFConnectorsTests\Live\Model\Application\Impl\Hubspot\Connector;

use Exception;
use Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot\Connector\HubSpotCreateContactConnector;
use Hanaboso\Utils\File\File;
use HbPFConnectorsTests\DatabaseTestCaseAbstract;
use HbPFConnectorsTests\DataProvider;

/**
 * Class HubspotCreateContactConnectorTest
 *
 * @package HbPFConnectorsTests\Live\Model\Application\Impl\Hubspot\Connector
 */
final class HubspotCreateContactConnectorTest extends DatabaseTestCaseAbstract
{

    /**
     * @group live
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $app                           = self::getContainer()->get('hbpf.application.hub-spot');
        $hubspotCreateContactConnector = new HubSpotCreateContactConnector();
        $hubspotCreateContactConnector
            ->setSender(self::getContainer()->get('hbpf.transport.curl_manager'))
            ->setDb($this->dm)
            ->setApplication($app);

        $this->pfd(
            DataProvider::getOauth2AppInstall(
                $app->getName(),
                'user',
                'CPn036zALRICAQEYu_j2AiDpsIEEKJSQDDIZAAoa8Oq06qseob1dXiP5KB1H3dY5AG0ShToPAAoCQQAADIADAAgAAAABQhkAChrw6vXLFCILvuPoTaMFCHwh43lT3Ura',
            ),
        );
        $this->dm->clear();

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
