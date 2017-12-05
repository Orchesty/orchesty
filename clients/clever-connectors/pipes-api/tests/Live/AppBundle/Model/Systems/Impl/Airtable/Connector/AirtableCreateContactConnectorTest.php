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

        $connector  = $this->container->get('hbpf.connector.airtable-create-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'url'     => 'https://api.airtable.com/v0/app91I09gFeMUscCG/Table%201',
            'api_key' => 'keyuejqSEf94ZjN11',
        ], [
            'fields' => [
                'Email' => 'email@example.com',
                'Name'  => 'First Name',
            ],
        ]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}