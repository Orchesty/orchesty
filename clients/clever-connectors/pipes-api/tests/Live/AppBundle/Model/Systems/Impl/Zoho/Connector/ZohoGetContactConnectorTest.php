<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Zoho\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoGetContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoGetContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.zoho-get-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'auth_token' => '05361930f1c8c009d9a1e30e07b23126',
        ], ['id' => '76762000000075669']));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}