<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class CMValidateSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
class CMValidateSubscriberConnector extends CMSubscriberConnectorAbstract
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

    /**
     * @param string $email
     *
     * @return string
     */
    protected function getUrl(string $email = ''): string
    {
        return sprintf('%s/validation/email', $this->getBaseUrl());
    }

}