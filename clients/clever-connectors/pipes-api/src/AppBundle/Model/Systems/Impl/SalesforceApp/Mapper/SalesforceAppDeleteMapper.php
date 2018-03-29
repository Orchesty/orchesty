<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper;

use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;

/**
 * Class SalesforceAppDeleteMapper
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\SalesforceApp\Mapper
 */
class SalesforceAppDeleteMapper extends SalesforceAppMapperAbstract
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

        if ((bool) $data[self::DELETED] === TRUE) {
            return FALSE;
        }

        return TRUE;
    }

}