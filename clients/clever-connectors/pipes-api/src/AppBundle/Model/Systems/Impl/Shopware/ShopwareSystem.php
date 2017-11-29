<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopware;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class ShopwareSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopware
 */
class ShopwareSystem extends PluginSystemAbstract
{

    protected const SWITCH_TOKEN               = 'clever_connector/switch_token';
    protected const SYNC_URL                   = 'clever_connector/subscriber?page=%s&limit=%s';
    protected const CREATE_SUBSCRIBER_URL      = 'clever_connector/create';
    protected const UNSUBSCRIBE_SUBSCRIBER_URL = 'clever_connector/unsubscribe?id=%s';
    protected const HARD_BOUNCE_SUBSCRIBER_URL = 'clever_connector/hard_bounce?id=%s';
    protected const SUBSCRIBE_SUBSCRIBER_URL   = 'clever_connector/subscribe?id=%s';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'shopware';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Shopware';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Shopware: the trendsetting ecommerce platform to power your online business';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

}