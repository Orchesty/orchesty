<?php
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/25/17
 * Time: 2:28 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Quickbooks\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksCustomerConnectorAbstract;
use CleverConnectors\AppBundle\Model\Systems\Impl\Quickbooks\Connector\QuickbooksSyncCustomerConnector;

class QuickbooksSyncCustomerConnectorTest extends QuickbooksCustomerConnectorAbstractTest
{

    /**
     * @covers ::getId
     *
     * @return void
     */
    public function testGetId(): void
    {
        $this->initMocks();

        $connector = $this->createConnector();

        $id = $connector->getId();

        $this->assertEquals('quickbooks-sync-customer-connector', $id);
    }

    /**
     * @return QuickbooksCustomerConnectorAbstract
     */
    protected function createConnector(): QuickbooksCustomerConnectorAbstract
    {
        return new QuickbooksSyncCustomerConnector(
            $this->system,
            $this->lastSyncManager,
            $this->factory,
            $this->dm
        );
    }
}