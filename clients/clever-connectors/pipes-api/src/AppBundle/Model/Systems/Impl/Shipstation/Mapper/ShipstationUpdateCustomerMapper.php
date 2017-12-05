<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;
use Nette\Utils\Strings;

/**
 * Class ShipstationUpdateCustomerMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Shipstation\Mapper
 */
class ShipstationUpdateCustomerMapper implements CustomNodeInterface
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

        if (!array_key_exists('email', $data)) {
            throw new CleverConnectorsException(
                'Missing required email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $subscriber = new CMSubscriber();
        $subscriber->setEmail($data['email']);

        if (array_key_exists('name', $data)) {
            $position = strrpos($data['name'], ' ');
            if ($position !== FALSE) {
                $subscriber
                    ->setFirstName(Strings::substring($data['name'], 0, $position))
                    ->setLastName(Strings::substring($data['name'], $position + 1));
            } else {
                $subscriber->setLastName($data['name']);
            }
        }

        if (array_key_exists('customerId', $data)) {
            $subscriber->setForeignId($data['customerId']);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

}