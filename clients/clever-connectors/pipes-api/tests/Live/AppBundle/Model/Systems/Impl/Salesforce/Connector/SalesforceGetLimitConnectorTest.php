<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Salesforce\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class SalesforceGetLimitConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Salesforce\Connector
 */
final class SalesforceGetLimitConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessEvent(): void
    {
        //$this->markTestSkipped();

        $connector  = $this->container->get('hbpf.connector.salesforce-get-limit-connector');
        $processDto = $connector->processAction(
            $this->prepareConnectorProcessDto(
                [
                    'access_token' => '00D0N000000h2Wg!AQEAQCCpCVtLA8gMuKk3.wZGyi1ZZQDxCPXIpFjsSzsjPuEGcSz8AHtv1NjkSNP_1oQrlmHucdjs1.5j87ECV8qNuSgaGvRF',
                    'instance_url' => 'https://na73.salesforce.com/',
                ]
            )
        );

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}