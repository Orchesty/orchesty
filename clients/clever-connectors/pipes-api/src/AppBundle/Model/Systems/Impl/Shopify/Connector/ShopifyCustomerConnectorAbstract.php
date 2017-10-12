<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;
use Hanaboso\PipesFramework\Connector\Exception\ConnectorException;

/**
 * Class ShopifyCustomerConnectorAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Connector
 */
abstract class ShopifyCustomerConnectorAbstract implements ConnectorInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws ConnectorException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        throw new ConnectorException(
            'Shopify has no support for action!',
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
        $arr = json_decode($dto->getData(), TRUE);
        if (!array_key_exists('data', $arr)) {
            throw new CleverConnectorsException(
                'Missing key [data] in dto from webhook request.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return $dto->setData(json_encode($arr['data']));
    }

}