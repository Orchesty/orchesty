<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper;

/**
 * Class SalesforceSyncContactMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Salesforce\Mapper
 */
class SalesforceSyncContactMapper extends SalesforceContactMapperAbstract
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
        return TRUE;
    }

}