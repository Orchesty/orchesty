<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellGetContactConnectorTest
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
final class NutshellGetContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.nutshell-get-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
        ], [CleverFieldsEnum::FOREIGN_ID => 407]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}