<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriptionConnector\CustomerObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class SalesforceUpdateContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
class SalesforceUpdateContactMapper implements CustomNodeInterface
{

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE)['data'];

        if (!array_key_exists('Email', $data)) {
            throw new CleverConnectorsException(
                'Missing required Email field in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $obj = new CMSubscriber();
        $obj->setEmail($data['Email']);

        if (array_key_exists('FirstName', $data)) {
            $obj->setFirstName($data['FirstName']);
        }

        if (array_key_exists('LastName', $data)) {
            $obj->setLastName($data['LastName']);
        }

        if (array_key_exists('Id', $data)) {
            $obj->setForeignId($data['Id']);
        }

        return $dto->setData(json_encode($obj->toArray()));
    }

}