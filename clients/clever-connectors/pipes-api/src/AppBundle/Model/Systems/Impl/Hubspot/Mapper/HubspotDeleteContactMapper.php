<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class HubspotDeleteContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotDeleteContactMapper extends HubspotMapperAbstract implements CustomNodeInterface
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

        $this->continueAfterBasicDataCheck($data);

        $allowedTypes = [
            HubspotSystem::SUBSCRIPTION_TYPE_CREATE,
            HubspotSystem::SUBSCRIPTION_TYPE_UPDATE,
        ];

        // we do not want creation/propertyChange to continue
        if (in_array($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY], $allowedTypes)) {
            return $this->setHeadersToStop($dto);
        }

        if (!array_key_exists('vid', $data)) {
            throw new CleverConnectorsException(
                'Missing required "vid" field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        // TODO Hubspot does not make it possible to fetch email of deleted entity

        $obj = new CMSubscriber();
        $obj
            ->setForeignId($data['vid'])
            ->setEmail((string) $data['vid'])
            ->setReactivate(FALSE);

        return $dto->setData(json_encode($obj->toArray()));
    }

}