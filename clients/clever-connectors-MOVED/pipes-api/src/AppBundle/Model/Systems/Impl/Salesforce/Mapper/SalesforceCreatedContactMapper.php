<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

/**
 * Class SalesforceCreatedContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
class SalesforceCreatedContactMapper extends SalesforceContactMapperAbstract
{

    /**
     * @var bool
     */
    protected $includeList = TRUE;

    /**
     * @param array $data
     *
     * @return bool
     */
    protected function checkDate(array $data): bool
    {
        return $data['CreatedDate'] === $data['LastModifiedDate'];
    }

}