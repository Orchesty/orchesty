<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;
use Nette\Utils\Json;

/**
 * Class BigcommerceCustomerConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Connector
 */
abstract class BigcommerceCustomerConnectorAbstract implements ConnectorInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Bigcommerce has no support for action!',
            ConnectorException::CONNECTOR_DOES_NOT_HAVE_PROCESS_BATCH
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

        if (!is_array($data) || !isset($data['data']['id'])) {
            throw new CleverConnectorsException(
                'Empty data or bad format.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData(sprintf('{"id":%s}', $data['data']['id']));
    }

}