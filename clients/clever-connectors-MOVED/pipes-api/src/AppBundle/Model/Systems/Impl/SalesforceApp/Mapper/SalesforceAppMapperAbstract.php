<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CM\SubscriberConnector\SubscriberObject\CMSubscriber;
use CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Connector\SalesforceAppMapFieldsConnector;
use CleverConnectors\AppBundle\Repository\SystemInstallRepository;
use CleverConnectors\AppBundle\Utils\HeadersUtils;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\CommonsBundle\Process\ProcessDto;
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
    public const FIELDS    = 'fields';
    public const CM_FIELD  = 'CMHB__cmIDField__c';
    public const ID_CUSTOM = 'CMHB__customIdField__c';

    protected const DISTRIBUTION_LIST = 'CMHB__Distribution_List__r';

    /**
     * @var SystemInstallRepository|ObjectRepository
     */
    protected $repo;

    /**
     * SalesforceAppMapperAbstract constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->repo = $dm->getRepository(SystemInstall::class);
    }

    /**
     * @param ProcessDto $dto
     *
     * @return ProcessDto
     */
    public function process(ProcessDto $dto): ProcessDto
    {
        $systemInstall = $this->repo->getSystemInstallFromHeaders($dto->getHeaders());

        $data = json_decode($dto->getData(), TRUE);
        $data = $this->normalizeData($data, $systemInstall);

        if (!is_array($data) || !array_key_exists(self::EMAIL, $data) || !array_key_exists(self::LIST, $data)) {
            HeadersUtils::setStopHeaderToDto($dto, 'Missing data or required field CMHB__Email__c or CMHB__CM_ID__c');

            return $dto;
        }

        try {
            if ($this->isSkippable($data)) {
                return HeadersUtils::setStopHeaderToDto($dto);
            }
        } catch (CleverConnectorsException $e) {
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

        if (array_key_exists(self::FIELDS, $data)) {
            foreach ($data[self::FIELDS] as $key => $field) {
                $subscriber->addCustomField((string) $key, $field);
            }
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
     * @param array         $data
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    protected function normalizeData(array $data, SystemInstall $systemInstall): array
    {
        $ret = [];

        if (isset($data[self::EMAIL])) {
            $ret[self::EMAIL] = $data[self::EMAIL];
        }

        if (isset($data[self::FIRSTNAME])) {
            $ret[self::FIRSTNAME] = $data[self::FIRSTNAME];
        }

        if (isset($data[self::LASTNAME])) {
            $ret[self::LASTNAME] = $data[self::LASTNAME];
        }

        if (isset($data[self::CREATED])) {
            $ret[self::CREATED] = $data[self::CREATED];
        }

        if (isset($data[self::UPDATED])) {
            $ret[self::UPDATED] = $data[self::UPDATED];
        }

        if (isset($data[self::DELETED])) {
            $ret[self::DELETED] = $data[self::DELETED];
        }

        if (isset($data[self::DISTRIBUTION_LIST][self::LIST])) {
            $ret[self::LIST] = $data[self::DISTRIBUTION_LIST][self::LIST];
        }

        $ret = $this->mapCustomFields($data, $ret, $systemInstall);

        return $ret;
    }

    /**
     * @param array         $input
     * @param array         $output
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    protected function mapCustomFields(array $input, array $output, SystemInstall $systemInstall): array
    {
        $mapFields = $systemInstall->getSettings()[SalesforceAppMapFieldsConnector::MAP_FIELDS] ?? [];

        foreach ($mapFields as $field) {
            $output['fields'][$field[self::CM_FIELD]] = $input[$field[self::ID_CUSTOM]] ?? '';
        }

        return $output;
    }

    /**
     * @param array $data
     *
     * @return bool
     * @throws CleverConnectorsException
     */
    abstract protected function isSkippable(array $data): bool;

}