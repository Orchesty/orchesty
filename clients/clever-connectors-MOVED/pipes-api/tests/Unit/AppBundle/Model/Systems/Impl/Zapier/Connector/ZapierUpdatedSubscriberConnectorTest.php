<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 3:05 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector\ZapierSubscriberConnectorAbstract;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector\ZapierUpdatedSubscriberConnector;

/**
 * Class ZapierUpdateubscriberConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Connector
 */
class ZapierUpdatedSubscriberConnectorTest extends ZapierSubscriberConnectorAbstractTest
{

    /**
     *
     */
    public function testGetId(): void
    {
        $this->assertEquals('zapier-updated-subscriber-connector', $this->createConnector()->getId());
    }

    /**
     * @return ZapierSubscriberConnectorAbstract
     */
    protected function createConnector(): ZapierSubscriberConnectorAbstract
    {
        return new ZapierUpdatedSubscriberConnector();
    }

}