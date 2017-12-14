<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\HubspotSystem;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;

/**
 * Class HubspotCreatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Mapper
 */
class HubspotCreatedContactMapper extends HubspotMapperAbstract
{

    /**
     * @var bool
     */
    protected $includeList = TRUE;

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

        // we do not want propertyChange/deletion to continue
        if ($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY] == HubspotSystem::SUBSCRIPTION_TYPE_UPDATE) {
            return $this->setHeadersToStop($dto);
        } elseif ($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY] == HubspotSystem::SUBSCRIPTION_TYPE_DELETE) {
            throw new CleverConnectorsException(
                sprintf('Disallowed subscription type "%s"', $data[HubspotSystem::SUBSCRIPTION_TYPE_KEY]),
                CleverConnectorsException::DISALLOWED_SUBSCRIPTION_TYPE
            );
        } elseif ($data[HubspotSystem::SUBSCRIPTION_TYPE_KEY] != HubspotSystem::SUBSCRIPTION_TYPE_CREATE) {
            throw new CleverConnectorsException(
                sprintf('Unknown subscription type "%s"', $data[HubspotSystem::SUBSCRIPTION_TYPE_KEY]),
                CleverConnectorsException::UNKNOWN_SUBSCRIPTION_TYPE
            );
        }

        $obj = $this->fillCMSubscriber($dto, $data);

        return $dto->setData(json_encode($obj->toArray()));
    }

}