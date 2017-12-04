<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Drupal;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class DrupalSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Drupal
 */
class DrupalSystem extends PluginSystemAbstract
{

    protected const SWITCH_TOKEN               = 'clever-connectors/switch-token';
    protected const CREATE_SUBSCRIBER_URL      = 'clever-connectors/subscriber/create';
    protected const SUBSCRIBE_SUBSCRIBER_URL   = 'clever-connectors/subscriber/subscribe/%s';
    protected const UNSUBSCRIBE_SUBSCRIBER_URL = 'clever-connectors/subscriber/unsubscribe/%s';
    protected const HARD_BOUNCE_SUBSCRIBER_URL = 'clever-connectors/subscriber/hard-bounce/%s';
    protected const SYNC_URL                   = 'clever-connectors/subscriber/%s/%s';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'drupal';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Drupal';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Drupal';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

}