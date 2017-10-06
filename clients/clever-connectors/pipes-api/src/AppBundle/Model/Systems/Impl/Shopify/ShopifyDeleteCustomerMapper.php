<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class ShopifyDeleteCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifyDeleteCustomerMapper implements ConnectorInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function processAction(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!array_key_exists('id', $data)) {
            throw new CleverConnectorsException(
                'Missing required id field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }
        $res = [
            'email' => $data['id'], //TODO Shopify does not send email that is required by cm...
        ];

        return $dto->setData(json_encode($res));
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-customer-delete-mapper';
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto|void
     * @throws Exception
     */
    public function processEvent(ProcessDto $dto): ProcessDto
    {
        throw new Exception('Shopify mapper has no process event.');
    }

}