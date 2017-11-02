<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class HubspotDeletedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotDeletedContactMapper extends HubspotMapperAbstract
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
        $disallowed = [
            HubspotSystem::SUBSCRIPTION_TYPE_CREATE,
            HubspotSystem::SUBSCRIPTION_TYPE_UPDATE,
        ];

        if (in_array($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY], $disallowed)) {
            return $this->setHeadersToStop($dto);
        } elseif ($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY] != HubspotSystem::SUBSCRIPTION_TYPE_DELETE) {
            throw new CleverConnectorsException(
                sprintf('Unknown subscription type "%s"', $data[HubspotSystem::SUBSCRIPTION_TYPE_KEY]),
                CleverConnectorsException::UNKNOWN_SUBSCRIPTION_TYPE
            );
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