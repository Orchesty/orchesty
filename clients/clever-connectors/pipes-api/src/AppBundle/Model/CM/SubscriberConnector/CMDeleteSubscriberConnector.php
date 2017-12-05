<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\CM\SubscriberConnector;

use CleverConnectors\AppBundle\Enum\CleverFieldsEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\CurlManager;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class CMDeleteSubscriberConnector
 *
 * @package CleverConnectors\AppBundle\Model\CM\SubscriberConnector
 */
class CMDeleteSubscriberConnector extends CMSubscriberConnectorAbstract
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
        $data = json_decode($dto->getData(), TRUE);
        if (is_array($data) && !array_key_exists(CleverFieldsEnum::EMAIL, $data)) {
            return (new ProcessDto())->setData('')->setHeaders($dto->getHeaders());
        }

        return $this->processCMAction($dto, CurlManager::METHOD_DELETE, [200], $data[CleverFieldsEnum::EMAIL]);
    }

}