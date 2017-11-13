<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class PluginWebhookSubscriberConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Plugins\Connector
 */
abstract class PluginWebhookSubscriberConnectorAbstract implements ConnectorInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Plugin sync has no support for event.',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_EVENT
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);
        if (empty($data) || !is_array($data) || !array_key_exists(CleverFieldsEnum::EMAIL, $data)) {
            throw new CleverConnectorsException(
                'Missing data in plugin webhook connector.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto;
    }

}