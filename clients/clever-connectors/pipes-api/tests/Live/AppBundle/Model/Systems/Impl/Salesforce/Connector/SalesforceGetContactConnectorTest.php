<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceGetContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceGetContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.salesforce-get-contact-connector');
        $processDto = $connector->processEvent($this->prepareConnectorProcessDto([
            'access_token' => '00D1I000001WyE7!ARAAQPT.YKcCvNCD6rFS.DD_N7cxWbz8hHppPhTSyvwCEWv5JqbemqJmXkWKoK8dGzYiZdpDEJjvI3V2Dv9EuIr6v0xd31Vs',
            'instance_url' => 'https://na73.salesforce.com/',
        ], ['id' => '0031I0000044B1kQAE']));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}