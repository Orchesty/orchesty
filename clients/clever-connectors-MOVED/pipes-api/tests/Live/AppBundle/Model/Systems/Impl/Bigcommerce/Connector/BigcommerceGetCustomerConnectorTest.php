<?php declare(strict_types=1);

namespace Tests\Live\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use Nette\Utils\Json;
use Tests\ConnectorTestCaseAbstract;

/**
 * Class BigcommerceGetCustomerConnectorTest
 *
 * @package Tests\Live\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
final class BigcommerceGetCustomerConnectorTest extends ConnectorTestCaseAbstract
{

    /**
     *
     */
    public function testProcessAction(): void
    {
        $this->markTestSkipped();
        $connector  = $this->container->get('hbpf.connector.bigcommerce-get-customer-connector');
        $processDto = $connector->processAction($this->prepareConnectorProcessDto([
            'store_id'     => 'ukcfcghi',
            'client_id'    => 'p7f4o1hfl1zdkz1bp1sy7u8qs0fq7q',
            'access_token' => '7ndpkdbqb0h1wycrxhtw43ye0yprtn9',
        ], ['id' => 1]));

        $this->assertTrue(is_array(Json::decode($processDto->getData(), TRUE)));
    }

}