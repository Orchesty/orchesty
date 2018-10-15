<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Utils\CMHeaders;
use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class NutshellUpdateContactConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
final class NutshellUpdateContactConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessActionUnSubscribe(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.nutshell-update-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
        ], [
            'jsonrpc' => '2.0',
            'id'      => 'contact',
            'method'  => 'editContact',
            'params'  => [
                'contactId' => 407,
                'rev'       => 0,
                'contact'   => [
                    'customFields' => [],
                ],
            ],
        ], [
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_UNSUBSCRIBE,
        ]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

    /**
     *
     */
    public function testProcessActionHardBounce(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.nutshell-update-contact-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'username' => 'nutshell@mailinator.com',
            'api_key'  => '967b1f7b321e6305d18e6656a650c32420aba98d',
        ], [
            'jsonrpc' => '2.0',
            'id'      => 'contact',
            'method'  => 'editContact',
            'params'  => [
                'contactId' => 407,
                'rev'       => 1,
                'contact'   => [
                    'customFields' => [],
                ],
            ],
        ], [
            CMHeaders::createKey(CMHeaders::CM_EVENT_TYPE) => SystemInstall::EVENT_HARD_BOUNCE,
        ]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}