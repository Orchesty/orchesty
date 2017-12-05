<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class HubspotSyncContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotSyncContactMapper extends HubspotMapperAbstract
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

        $this->continueAfterDataCheck('properties', $data);

        $properties = $data['properties'];

        $email = $this->getEmail($data);

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