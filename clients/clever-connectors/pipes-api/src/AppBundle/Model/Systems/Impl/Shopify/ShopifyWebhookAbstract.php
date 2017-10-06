<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class ShopifyWebhookAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify
 */
abstract class ShopifyWebhookAbstract implements ConnectorInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws Exception
     */
    public function processAction(ProcessDto $dto): ProcessDto
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

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws Exception
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new Exception('Shopify has no process event.');
    }

}