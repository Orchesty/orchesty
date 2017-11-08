<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Model\Plugins\PluginSystemAbstract;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class PluginCreateSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
class PluginCreateSubscriberConnector extends PluginSubscriberConnectorAbstract
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'plugin-create-contact';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return string
     */
    protected function getBody(ProcessDto $dto): string
    {
        return $dto->getData();
    }

    /**
     * @param PluginSystemAbstract $system
     * @param ProcessDto           $dto
     *
     * @return string
     */
    protected function getUri(PluginSystemAbstract $system, ProcessDto $dto): string
    {
        return $system->getCreateSubscriberUrl();
    }

}