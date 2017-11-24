<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriptionConnector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class CMValidateSubscriptionConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriptionConnector
 */
class CMValidateSubscriptionConnector extends CMSubscriptionConnectorAbstract
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
        return 'https://api.dev.clevermonitor.com/v1.2/validation/email';
    }

}