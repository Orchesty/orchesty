<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Airtable\Connector;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class AirtableUpdateContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Airtable\Connector
 */
final class AirtableUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessActionUnSubscribe(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.airtable-update-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto(
            [
                'url'     => 'https://api.airtable.com/v0/app91I09gFeMUscCG/Table%201',
                'api_key' => 'keyuejqSEf94ZjN11',
            ],
            [
                CleverFieldsEnum::FOREIGN_ID => 'recCP5JwCKvgxG584',
                'fields'                     => ['air_unsubscribe' => TRUE],
            ]
        ));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

    /**
     *
     */
    public function testProcessActionHardBounce(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.airtable-update-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto(
            [
                'url'     => 'https://api.airtable.com/v0/app91I09gFeMUscCG/Table%201',
                'api_key' => 'keyuejqSEf94ZjN11',
            ],
            [
                CleverFieldsEnum::FOREIGN_ID => 'recCP5JwCKvgxG584',
                'fields'                     => ['air_hard_bounce' => TRUE],
            ]
        ));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}