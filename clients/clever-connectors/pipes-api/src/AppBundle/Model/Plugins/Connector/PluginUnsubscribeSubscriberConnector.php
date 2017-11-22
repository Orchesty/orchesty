<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class PluginUnsubscribeSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginUnsubscribeSubscriberConnector extends PluginSubscriberConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-unsubscribe-contact';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return string
     */
    protected function getBody(ProcessDto $dto): string
    {
        return '{}';
    }

    /**
     * @param PluginSystemAbstract $system
     * @param ProcessDto           $dto
     *
     * @return string
     */
    protected function getUri(PluginSystemAbstract $system, ProcessDto $dto): string
    {
        return sprintf($system->getUsubscribeSubscriberUrl(), $this->getIdFromDto($dto));
    }

}