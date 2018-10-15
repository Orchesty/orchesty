<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Zoho\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class ZohoCreateContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Zoho\Connector
 */
final class ZohoCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.zoho-create-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'auth_token' => '05361930f1c8c009d9a1e30e07b23126',
        ], [
            'xml' => '<Contacts><row no="1"><FL val="Email">email@example.com</FL><FL val="First Name">First Name</FL><FL val="Last Name">Last Name</FL></row></Contacts>',
        ]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}