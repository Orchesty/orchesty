<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Airtable\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class AirtableCreateContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
final class AirtableCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();

        // TODO finish when mapping's ready

        $connector  = $this->container->get('hbpf.connector.airtable-create-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'url'     => 'https://api.airtable.com/v0/app91I09gFeMUscCG/Table%201',
            'api_key' => 'keyuejqSEf94ZjN11',
        ], [
            'email'     => 'email@example.com',
            'firstName' => 'First Name',
            'lastName'  => 'Last Name',
        ]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}