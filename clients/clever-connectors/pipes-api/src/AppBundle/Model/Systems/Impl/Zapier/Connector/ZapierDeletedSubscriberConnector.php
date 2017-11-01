<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 10/31/17
 * Time: 11:59 AM
 */

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector;

/**
 * Class ZapierDeletedSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector
 */
class ZapierDeletedSubscriberConnector extends ZapierSubscriberConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'zapier-deleted-subscriber-connector';
    }

}
