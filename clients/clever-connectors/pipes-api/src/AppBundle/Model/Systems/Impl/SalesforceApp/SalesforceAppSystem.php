<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;

/**
 * Class SalesforceAppSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp
 */
class SalesforceAppSystem extends PluginSystemAbstract
{

    protected const SWITCH_TOKEN = '';
    protected const SYNC_URL     = '';

    /**
     * SalesforceAppSystem constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->cmEvents = [];
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'salesforceapp';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'SalesForce App';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'SalesForce App ...';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'logo';
    }

}