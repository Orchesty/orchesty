<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 21.9.17
 * Time: 17:49
 */

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class CMCreateSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
class CMCreateSubscriberConnector extends CMSubscriberConnectorAbstract
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
        return $this->processCMAction($dto, CurlManager::METHOD_POST, [200, 201]);
    }

}