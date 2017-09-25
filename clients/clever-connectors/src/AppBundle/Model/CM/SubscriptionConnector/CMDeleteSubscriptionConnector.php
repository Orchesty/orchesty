<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriptionConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class CMDeleteSubscriptionConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriptionConnector
 */
class CMDeleteSubscriptionConnector extends CMSubscriptionConnectorAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $eml = json_decode($dto->getData(), TRUE);
        if (!isset($eml['email'])) {
            throw new CleverConnectorsException(
                'Required email in data for url creation.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $this->processCMAction($dto, 'DELETE', [200], $eml['email']);
    }

}