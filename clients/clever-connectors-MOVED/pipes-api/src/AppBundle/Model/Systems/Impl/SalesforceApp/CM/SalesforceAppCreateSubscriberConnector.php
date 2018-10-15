<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\CM;

/**
 * Created by PhpStorm.
 * User: radekj
 * Date: 21.9.17
 * Time: 17:49
 */

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\CMSubscriberConnectorAbstract;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class SalesforceAppCreateSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
class SalesforceAppCreateSubscriberConnector extends CMSubscriberConnectorAbstract
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     * @throws ConnectorException
     * @throws CurlException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        try {
            return $this->processCMAction($dto, CurlManager::METHOD_POST, [200, 201]);
        } catch (ConnectorException $e) {
            if ($e->getCode() === ConnectorException::CONNECTOR_FAILED_TO_PROCESS) {
                return $dto->setData('');
            }

            throw $e;
        }
    }

}