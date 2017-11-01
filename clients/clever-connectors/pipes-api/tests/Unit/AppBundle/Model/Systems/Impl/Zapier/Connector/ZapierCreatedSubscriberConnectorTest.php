<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 3:05 PM
 */

namespace Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Connector;

use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector\ZapierCreatedSubscriberConnector;
use CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector\ZapierSubscriberConnectorAbstract;

/**
 * Class ZapierCreateSubscriberConnectorTest
 *
 * @package Tests\Unit\AppBundle\Model\Systems\Impl\Zapier\Connector
 */
class ZapierCreatedSubscriberConnectorTest extends ZapierSubscriberConnectorAbstractTest
{

    /**
     *
     */
    public function testGetId(): void
    {
        $this->assertEquals('zapier-created-subscriber-connector', $this->createConnector()->getId());
    }

    /**
     * @return ZapierSubscriberConnectorAbstract
     */
    protected function createConnector(): ZapierSubscriberConnectorAbstract
    {
        return new ZapierCreatedSubscriberConnector();
    }

}