<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 11:59 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector;

/**
 * Class ZapierUpdatedSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector
 */
class ZapierUpdatedSubscriberConnector extends ZapierSubscriberConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zapier-updated-subscriber-connector';
    }

}
