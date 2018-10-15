<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;

/**
 * Class PluginSubscribeSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginSubscribeSubscriberConnector extends PluginSubscriberConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-subscribe-contact';
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
     * @throws CleverConnectorsException
     */
    protected function getUri(PluginSystemAbstract $system, ProcessDto $dto): string
    {
        return sprintf($system->getSubscribeSubscriberUrl(), $this->getIdFromDto($dto));
    }

}