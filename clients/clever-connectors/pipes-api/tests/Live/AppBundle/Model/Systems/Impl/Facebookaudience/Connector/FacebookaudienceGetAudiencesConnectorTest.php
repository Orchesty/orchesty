<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceGetAudiencesConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceGetAudiencesConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws SystemException
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();

        $connector  = $this->container->get('hbpf.connector.facebookaudience-get-audiences-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'access_token' => 'EAAUmsI0AZCFEBAEdbSZBiF4tAxQOReSMB6rWu88KBKoM6GLWYPPokhgLKHSeWmZA1dirRGey9kuCwZAui0LZAaPWzY50GtcM0SZBK60fkiIfoOZB1cWVgG7abN5fGavAZB0Jsv8l0mGgCjmVbgh6YbZAZApKVkb9N0lKoZD',
        ]));

        $data = Json::decode($processDto->getData(), TRUE);
        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data'][0]);
        $this->assertArrayHasKey('name', $data['data'][0]);
    }

}