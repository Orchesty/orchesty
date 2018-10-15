<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;

/**
 * Class SalesforceAppUpdateMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper
 */
class SalesforceAppUpdateMapper extends SalesforceAppMapperAbstract
{

    /**
     * @param array $data
     *
     * @return bool
     * @throws CleverConnectorsException
     */
    protected function isSkippable(array $data): bool
    {
        $this->checkData($data);

        if ((bool) $data[self::DELETED] === FALSE && ($data[self::CREATED] !== $data[self::UPDATED])) {
            return FALSE;
        }

        return TRUE;
    }

}