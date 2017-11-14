<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Opencart;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class OpencartSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Opencart
 */
class OpencartSystem extends PluginSystemAbstract
{

    protected const CREATE_SUBSCRIBER_URL      = 'index.php?route=extension/module/clever_connectors/subscriber_create';
    protected const UNSUBSCRIBE_SUBSCRIBER_URL = 'index.php?route=extension/module/clever_connectors/subscriber_unsubscribe&id=%s';
    protected const HARD_BOUNCE_SUBSCRIBER_URL = 'index.php?route=extension/module/clever_connectors/subscriber_hard_bounce&id=%s';
    protected const SYNC_URL                   = 'index.php?route=extension/module/clever_connectors/subscriber&page=%s&limit=%s';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'opencart';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Opencart';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Opencart';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

}