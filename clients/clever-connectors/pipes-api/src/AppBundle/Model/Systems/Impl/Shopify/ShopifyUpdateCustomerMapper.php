<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Exception;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\Connector\ConnectorInterface;

/**
 * Class ShopifyUpdateCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifyUpdateCustomerMapper implements ConnectorInterface
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

        if (!array_key_exists('email', $data)) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $res = [
            'email' => $data['email'],
        ];

        if (array_key_exists('first_name', $data)) {
            $res['first_name'] = $data['first_name'];
        }
        if (array_key_exists('last_name', $data)) {
            $res['last_name'] = $data['last_name'];
        }

        if (!empty($data['addresses'])) {
            if (array_key_exists('company', $data['addresses'][0])) {
                $res['company'] = $data['addresses'][0]['company'];
            }
            if (array_key_exists('city', $data['addresses'][0])) {
                $res['contact'] = $data['addresses'][0]['city'];
            }
        }

        return $dto->setData(json_encode($res));
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return 'shopify-customer-update-mapper';
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