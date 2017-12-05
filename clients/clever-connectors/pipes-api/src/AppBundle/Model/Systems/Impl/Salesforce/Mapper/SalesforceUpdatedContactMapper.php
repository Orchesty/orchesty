<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;
use Nette\Utils\Json;

/**
 * Class SalesforceUpdatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
class SalesforceUpdatedContactMapper implements CustomNodeInterface
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

        if (!is_array($data) || !array_key_exists('Email', $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field email',
                CleverConnectorsException::MISSING_DATA
            );
        }

        $subscriber = (new CMSubscriber())->setEmail($data['Email']);

        if (array_key_exists('FirstName', $data)) {
            $subscriber->setFirstName($data['FirstName']);
        }

        if (array_key_exists('LastName', $data)) {
            $subscriber->setLastName($data['LastName']);
        }

        if (array_key_exists('Id', $data)) {
            $subscriber->setForeignId($data['Id']);
        }

        return $dto->setData(Json::encode($subscriber->toArray()));
    }

}