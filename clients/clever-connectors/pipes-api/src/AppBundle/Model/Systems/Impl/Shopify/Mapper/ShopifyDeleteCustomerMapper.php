<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class ShopifyDeleteCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Mapper
 */
class ShopifyDeleteCustomerMapper implements CustomNodeInterface
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

        if (!array_key_exists('id', $data)) {
            throw new CleverConnectorsException(
                'Missing required id field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj
            ->setForeignId($data['id'])
            //TODO Shopify does not send email that is required by cm...
            ->setEmail((string) $data['id'])
            ->setReactivate(FALSE);

        return $dto->setData(json_encode($obj->toArray()));
    }

}