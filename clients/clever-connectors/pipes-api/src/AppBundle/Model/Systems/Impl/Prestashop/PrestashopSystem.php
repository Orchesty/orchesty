<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Prestashop;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class PrestashopSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Prestashop
 */
class PrestashopSystem extends PluginSystemAbstract
{

    protected const SWITCH_TOKEN               = 'index.php?fc=module&module=cleverconnector&controller=switch_token';
    protected const SYNC_URL                   = 'index.php?fc=module&module=cleverconnector&controller=subscriber&page=%s&limit=%s';
    protected const CREATE_SUBSCRIBER_URL      = 'index.php?fc=module&module=cleverconnector&controller=subscriber&action=create';
    protected const UNSUBSCRIBE_SUBSCRIBER_URL = 'index.php?fc=module&module=cleverconnector&controller=subscriber&action=unsubscribe&id=%s';
    protected const HARD_BOUNCE_SUBSCRIBER_URL = 'index.php?fc=module&module=cleverconnector&controller=subscriber&action=hard_bounce&id=%s';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'prestashop';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Prestashop';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Prestashop ...';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

}