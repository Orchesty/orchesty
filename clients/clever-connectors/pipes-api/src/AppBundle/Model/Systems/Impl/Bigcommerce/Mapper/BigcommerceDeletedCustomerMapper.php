<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class BigcommerceDeletedCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Mapper
 */
class BigcommerceDeletedCustomerMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = Json::decode($dto->getData(), TRUE);
        if (!array_key_exists('id', $data)) {
            throw new CleverConnectorsException(
                'Missing required id field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        // TODO: BigCommerce doesn't send email...
        $subscriber = new CMSubscriber();
        $subscriber
            ->setForeignId($data['id'])
            ->setEmail((string) $data['id'])
            ->setReactivate(FALSE);

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

}