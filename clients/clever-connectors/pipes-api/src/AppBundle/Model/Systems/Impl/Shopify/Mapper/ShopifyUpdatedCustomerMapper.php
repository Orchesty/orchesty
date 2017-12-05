<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ShopifyUpdatedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
class ShopifyUpdatedCustomerMapper implements CustomNodeInterface
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

        $obj = new CMSubscriber();
        $obj->setEmail($data['email']);

        if (array_key_exists('first_name', $data)) {
            $obj->setFirstName($data['first_name']);
        }

        if (array_key_exists('last_name', $data)) {
            $obj->setLastName($data['last_name']);
        }

        if (array_key_exists('id', $data)) {
            $obj->setForeignId($data['id']);
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

}