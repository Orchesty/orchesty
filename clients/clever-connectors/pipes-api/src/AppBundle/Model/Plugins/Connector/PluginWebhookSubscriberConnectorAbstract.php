<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Plugins\Connector;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
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
     * @return ProcessDto
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

        $obj = new CMSubscriber();
        $obj->setForeignId($data[CleverFieldsEnum::FOREIGN_ID] ?? '')
            ->setLastName($data[CleverFieldsEnum::LAST_NAME] ?? '')
            ->setFirstName($data[CleverFieldsEnum::FIRST_NAME] ?? '')
            ->setEmail($data[CleverFieldsEnum::EMAIL] ?? '')
            ->setLists(isset($data[CleverFieldsEnum::PLUGINS_LISTS]) ? [$data[CleverFieldsEnum::PLUGINS_LISTS]] : []);

        return $dto->setData(json_encode($obj->toArray()));
    }

}