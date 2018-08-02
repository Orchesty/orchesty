<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Zapier\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class ZapierSubscriberConnectorAbstract
 *
 * @package AppBundle\Model\Systems\Impl\Zapier\Connector
 */
abstract class ZapierSubscriberConnectorAbstract implements ConnectorInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);
        if (!is_array($data) || empty($data['email'])) {
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
            'Zapier has no support for action!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_ACTION
        );
    }

}