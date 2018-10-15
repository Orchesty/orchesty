<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceGetStoreConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceGetStoreConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.bigcommerce-get-store-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'store_id'     => 'tr289y8kqz',
            'client_id'    => 'jrkhq3mpfz4mvzwmzhjk0ysbtbtx634',
            'access_token' => 'hkxw1astc8f6zn183you1tdzdlhqys6',
        ], ['id' => 1]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}