<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

/**
 * Class PluginHardBounceSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginHardBounceSubscriberConnector extends PluginUnsubscribeSubscriberConnector
{

    protected const SUB_URL = 'clever_connector/subscriber/%s/hard_bounce';

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-hard-bounce-contact';
    }

}