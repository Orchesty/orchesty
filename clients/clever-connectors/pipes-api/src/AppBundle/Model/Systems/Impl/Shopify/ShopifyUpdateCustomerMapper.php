<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ShopifyUpdateCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify
 */
class ShopifyUpdateCustomerMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
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

        return $dto->setData(json_encode($res));
    }

}