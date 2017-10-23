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

        $this->continueAfterDataCheck(HubspotSystem::SUBSCRIPTION_TYPE_KEY, $data);

        // we do not want creation/propertyChange to continue
        if ($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY] != HubspotSystem::SUBSCRIPTION_TYPE_DELETE) {
            return $this->setHeadersToStop($dto);
        }

        $this->continueAfterDataCheck(HubspotSystem::OBJECT_ID_KEY, $data);

        // TODO Hubspot does not make it possible to fetch email of deleted entity

        $obj = new CMSubscriber();
        $obj
            ->setForeignId($data[HubspotSystem::OBJECT_ID_KEY])
            ->setEmail((string) $data[HubspotSystem::OBJECT_ID_KEY])
            ->setReactivate(FALSE);

        return $dto->setData(json_encode($obj->toArray()));
    }

}