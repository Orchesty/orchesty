<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class HubspotUpdateContactConnector
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Connector
 */
class HubspotUpdateContactConnector implements ConnectorInterface
{

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'hubspot-update-contact-connector';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $arr = json_decode($dto->getData(), TRUE);
        if (!is_array($arr) || empty($arr)) {
            throw new CleverConnectorsException(
                'Empty data or bad format.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto;
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Hubspot has no support for action!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_BATCH
        );
    }

}