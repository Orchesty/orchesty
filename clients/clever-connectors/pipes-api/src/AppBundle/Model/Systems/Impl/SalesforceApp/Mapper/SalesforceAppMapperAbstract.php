<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Hanaboso\PipesFramework\Commons\Process\ProcessDto;
use Hanaboso\PipesFramework\CustomNode\CustomNodeInterface;

/**
 * Class SalesforceAppMapperAbstract
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper
 */
abstract class SalesforceAppMapperAbstract implements CustomNodeInterface
{

    public const EMAIL     = 'CMHB__Email__c';
    public const FIRSTNAME = 'CMHB__Firstname__c';
    public const LASTNAME  = 'CMHB__Lastname__c';
    public const LIST      = 'CMHB__CM_ID__c';
    public const UPDATED   = 'LastModifiedDate';
    public const CREATED   = 'CreatedDate';
    public const DELETED   = 'CMHB__Deleted__c';

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     * @throws CleverConnectorsException
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $data = json_decode($dto->getData(), TRUE);

        if (!is_array($data) || !array_key_exists(self::EMAIL, $data) || !array_key_exists(self::LIST, $data)) {
            throw new CleverConnectorsException(
                'Missing data or required field CMHB__Email__c or CMHB__CM_ID__c',
                CleverConnectorsException::MISSING_DATA
            );
        }

        if ($this->isSkippable($data)) {
            return HeadersUtils::setStopHeaderToDto($dto);
        }

        $subscriber = new CMSubscriber();
        $subscriber
            ->setEmail($data[self::EMAIL])
            ->setLists([$data[self::LIST]]);

        if (array_key_exists(self::FIRSTNAME, $data)) {
            $subscriber->setFirstName($data[self::FIRSTNAME]);
        }

        if (array_key_exists(self::LASTNAME, $data)) {
            $subscriber->setLastName($data[self::LASTNAME]);
        }

        return $dto->setData(json_encode($subscriber->toArray()));
    }

    /**
     * @param array $data
     *
     * @throws CleverConnectorsException
     */
    protected function checkData(array $data): void
    {
        if (
            !array_key_exists(self::CREATED, $data) ||
            !array_key_exists(self::UPDATED, $data) ||
            !array_key_exists(self::DELETED, $data)
        ) {
            throw new CleverConnectorsException(
                'Missing required date fields in data.',
                CleverConnectorsException::MISSING_DATA
            );
        }
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    abstract protected function isSkippable(array $data): bool;

}