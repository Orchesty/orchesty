<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Json;

/**
 * Class NutshellUpdatedContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell\Connector
 */
class NutshellUpdatedContactConnector implements ConnectorInterface
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'nutshell-updated-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Nutshell has no support for action!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);
        if (!is_array($data)
            || !isset($data['events'][0]['payloadType'])
            || !isset($data['events'][0]['action'])
            || !isset($data['payloads'][0]['emails'][0]['value'])
        ) {
            throw new CleverConnectorsException(
                'Empty data or bad format.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto;
    }

}