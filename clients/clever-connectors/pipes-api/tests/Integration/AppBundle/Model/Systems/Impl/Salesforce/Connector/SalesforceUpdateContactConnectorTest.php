<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceUpdateContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessActionUnSubscribe(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.salesforce-update-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'access_token' => '00D1I000001WyE7!ARAAQPT.YKcCvNCD6rFS.DD_N7cxWbz8hHppPhTSyvwCEWv5JqbemqJmXkWKoK8dGzYiZdpDEJjvI3V2Dv9EuIr6v0xd31Vs',
            'instance_url' => 'https://na73.salesforce.com/',
        ],
            [CleverFieldsEnum::FOREIGN_ID => '0031I0000044SrpQAE'],
            [CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE]
        ));

        $this->assertEmpty($processDto->getData());
    }

    /**
     *
     */
    public function testProcessActionHardBounce(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.salesforce-update-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'access_token' => '00D1I000001WyE7!ARAAQPT.YKcCvNCD6rFS.DD_N7cxWbz8hHppPhTSyvwCEWv5JqbemqJmXkWKoK8dGzYiZdpDEJjvI3V2Dv9EuIr6v0xd31Vs',
            'instance_url' => 'https://na73.salesforce.com/',
        ],
            [CleverFieldsEnum::FOREIGN_ID => '0031I0000044SrpQAE'],
            [CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE]
        ));

        $this->assertEmpty($processDto->getData());
    }

}