<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Joomla;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class JoomlaSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Joomla
 */
class JoomlaSystem extends PluginSystemAbstract
{

    protected const SWITCH_TOKEN               = 'administrator/index.php?cc_controller=switch_token';
    protected const SYNC_URL                   = 'administrator/index.php?cc_controller=subscribers&page=%s&limit=%s';
    protected const CREATE_SUBSCRIBER_URL      = 'administrator/index.php?cc_controller=subscribers&action=create';
    protected const UNSUBSCRIBE_SUBSCRIBER_URL = 'administrator/index.php?cc_controller=subscribers&action=unsubscribe&id=%s';
    protected const HARD_BOUNCE_SUBSCRIBER_URL = 'administrator/index.php?cc_controller=subscribers&action=hard_bounce&id=%s';
    protected const SUBSCRIBE_SUBSCRIBER_URL   = 'administrator/index.php?cc_controller=subscribers&action=subscribe&id=%s';

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'joomla';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Joomla';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Joomla ...';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

}