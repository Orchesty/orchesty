<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

/**
 * Class PluginUpdatedSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginUpdatedSubscriberConnector extends PluginWebhookSubscriberConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-updated-subscribers';
    }

}