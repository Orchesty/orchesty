<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriptionConnector;

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 21.9.17
 * Time: 17:49
 */

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class CMCreateSubscriptionConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriptionConnector
 */
class CMCreateSubscriptionConnector extends CMSubscriptionConnectorAbstract
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
        return $this->processCMAction($dto, 'POST', [200, 201]);
    }

}