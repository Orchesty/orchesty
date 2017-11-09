<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellCreateContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
final class NutshellCreateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.nutshell-create-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
        ], [
                'jsonrpc' => '2.0',
                'id'      => 'contact',
                'method'  => 'newContact',
                'params'  => [
                    'contact' => [
                        'name'  => [
                            'givenName'  => 'First Name',
                            'familyName' => 'Last Name',
                        ],
                        'email' => ['email@example.com'],
                    ],
                ],
            ]
        ));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}