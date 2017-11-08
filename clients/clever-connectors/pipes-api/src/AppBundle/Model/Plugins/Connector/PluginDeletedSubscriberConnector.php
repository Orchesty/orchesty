<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

/**
 * Class PluginDeletedSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginDeletedSubscriberConnector extends PluginWebhookSubscriberConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-deleted-subscribers';
    }

}