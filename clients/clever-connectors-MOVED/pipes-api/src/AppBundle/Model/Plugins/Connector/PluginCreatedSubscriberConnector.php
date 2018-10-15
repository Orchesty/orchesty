<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

/**
 * Class PluginCreatedSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginCreatedSubscriberConnector extends PluginWebhookSubscriberConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-created-subscriber';
    }

}