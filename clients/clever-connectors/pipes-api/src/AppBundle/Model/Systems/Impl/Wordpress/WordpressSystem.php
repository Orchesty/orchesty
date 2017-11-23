<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Wordpress;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class WordpressSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Wordpress
 */
class WordpressSystem extends PluginSystemAbstract
{

    protected const SWITCH_TOKEN               = 'wp-json/cm-plugin/v1/clever_monitor/switch_token';
    protected const SYNC_URL                   = 'wp-json/cm-plugin/v1/clever_monitor/subscriber?page=%s&limit=%s';
    protected const CREATE_SUBSCRIBER_URL      = 'wp-json/cm-plugin/v1/clever_monitor/create';
    protected const UNSUBSCRIBE_SUBSCRIBER_URL = 'wp-json/cm-plugin/v1/clever_monitor/unsubscribe?id=%s';
    protected const HARD_BOUNCE_SUBSCRIBER_URL = 'wp-json/cm-plugin/v1/clever_monitor/hard_bounce?id=%s';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'wordpress';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Wordpress';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Wordpress ...';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

}