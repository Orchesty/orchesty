<?php declare(strict_types=1);

namespace CcApi\ApiEntity;

/**
 * Class ConnectorFactory
 *
 * @package CcApi\ApiEntity
 */
class ConnectorFactory
{

    private const SYSTEM_KEY     = 'system_key';
    private const SYSTEM_NAME    = 'system_name';
    private const USERS_COUNT    = 'users_count';
    private const REQUESTS_COUNT = 'requests_count';

    /**
     * @param array $data
     *
     * @return Connector
     */
    public static function create(array $data): Connector
    {
        $connector = new Connector();

        $connector
            ->setSystemKey($data[self::SYSTEM_KEY] ?? '')
            ->setSystemName($data[self::SYSTEM_NAME] ?? '')
            ->setUsersCount($data[self::USERS_COUNT] ?? 0)
            ->setRequestsCount($data[self::REQUESTS_COUNT] ?? 0);

        return $connector;
    }

}