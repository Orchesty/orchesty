<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class HubspotUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotUpdateContactMapper extends HubspotMapperAbstract implements CustomNodeInterface
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

        // we do not want creation/deletion to continue
        if ($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY] != HubspotSystem::SUBSCRIPTION_TYPE_UPDATE) {
            return $this->setHeadersToStop($dto);
        }

        $this->continueAfterDataCheck('properties', $data);

        $properties = $data['properties'];
        $email      = $this->getEmail($data);

        $obj = new CMSubscriber();
        $obj->setEmail($email);

        if (array_key_exists('firstname', $properties)) {
            $obj->setFirstName($properties['firstname']['value']);
        }

        if (array_key_exists('lastname', $properties)) {
            $obj->setLastName($properties['lastname']['value']);
        }

        if (array_key_exists('vid', $data)) {
            $obj->setForeignId($data['vid']);
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

}