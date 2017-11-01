<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceCreateContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.salesforce-create-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'access_token' => '00D1I000001WyE7!ARAAQPT.YKcCvNCD6rFS.DD_N7cxWbz8hHppPhTSyvwCEWv5JqbemqJmXkWKoK8dGzYiZdpDEJjvI3V2Dv9EuIr6v0xd31Vs',
            'instance_url' => 'https://na73.salesforce.com/',
        ], [
            'email'     => 'email@example.com',
            'firstName' => 'First Name',
            'lastName'  => 'Last Name',
        ]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}