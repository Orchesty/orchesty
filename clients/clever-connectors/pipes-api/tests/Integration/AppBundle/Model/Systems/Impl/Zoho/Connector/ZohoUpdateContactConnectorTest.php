<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Zoho\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoUpdateContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessActionUnSubscribe(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.zoho-update-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'auth_token' => '05361930f1c8c009d9a1e30e07b23126',
        ],
            [CleverFieldsEnum::FOREIGN_ID => '76762000000075669'],
            [CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE]
        ));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

    /**
     *
     */
    public function testProcessActionHardBounce(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.zoho-update-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'auth_token' => '05361930f1c8c009d9a1e30e07b23126',
        ],
            [CleverFieldsEnum::FOREIGN_ID => '76762000000075669'],
            [CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE]
        ));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}