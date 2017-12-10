<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class FacebookaudienceGetAccountsConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Facebookaudience\Connector
 */
final class FacebookaudienceGetAccountsConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     * @throws SystemException
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();

        $connector  = $this->container->get('hbpf.connector.facebookaudience-get-accounts-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'access_token' => 'EAAUmsI0AZCFEBAEdbSZBiF4tAxQOReSMB6rWu88KBKoM6GLWYPPokhgLKHSeWmZA1dirRGey9kuCwZAui0LZAaPWzY50GtcM0SZBK60fkiIfoOZB1cWVgG7abN5fGavAZB0Jsv8l0mGgCjmVbgh6YbZAZApKVkb9N0lKoZD',
        ]));

        $data = Json::decode($processDto->getData(), TRUE);
        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data['data'][0]);
        $this->assertArrayHasKey('name', $data['data'][0]);
    }

    /**
     * @throws SystemException
     */
    public function testGetAccounts(): void
    {
        $this->markTestSkipped();

        $processDto = $this->prepareConnectorProcessDto([
            'access_token' => 'EAAUmsI0AZCFEBAEdbSZBiF4tAxQOReSMB6rWu88KBKoM6GLWYPPokhgLKHSeWmZA1dirRGey9kuCwZAui0LZAaPWzY50GtcM0SZBK60fkiIfoOZB1cWVgG7abN5fGavAZB0Jsv8l0mGgCjmVbgh6YbZAZApKVkb9N0lKoZD',
        ], [], [], TRUE);

        $data = Json::decode($processDto->getData(), TRUE);
        /** @var SystemInstall $systemInstall */
        $systemInstall = $this->systemInstallRepository->find($data['system_install']['_id']);

        $connector  = $this->container->get('hbpf.connector.facebookaudience-get-accounts-connector');
        $accounts = $connector->getAccounts($systemInstall);

        $this->assertTrue(is_array($accounts));
        $this->assertArrayHasKey('id', $accounts[0]);
        $this->assertArrayHasKey('name', $accounts[0]);
    }

}