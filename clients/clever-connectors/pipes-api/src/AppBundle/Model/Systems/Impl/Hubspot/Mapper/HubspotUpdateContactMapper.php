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

        $this->continueAfterBasicDataCheck($data);

        // we do not want deletion to continue
        if ($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY] == HubspotSystem::SUBSCRIPTION_TYPE_DELETE) {
            return $this->setHeadersToStop($dto);
        }

        $properties = $data['properties'];

        if (!array_key_exists('email', $properties) || !array_key_exists('value', $properties['email'])) {
            throw new CleverConnectorsException(
                'Missing required "email" field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj->setEmail($properties['email']['value']);

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