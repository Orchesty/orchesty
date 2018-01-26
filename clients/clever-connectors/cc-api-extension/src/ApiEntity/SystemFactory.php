<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 3:35 PM
 */

namespace CcApi\ApiEntity;

/**
 * Class SystemFactory
 *
 * @package CcApi\ApiEntity
 */
class SystemFactory
{

    private const KEY           = 'key';
    private const NAME          = 'name';
    private const DESC          = 'description';
    private const TYPE          = 'type';
    private const AUTH_TYPE     = 'auth_type';
    private const USER_COUNT    = 'user_count';
    private const REQUEST_COUNT = 'request_count';

    /**
     * @param array $data
     *
     * @return System
     */
    public static function create(array $data): System
    {
        $system = new System();

        $system
            ->setKey($data[self::KEY] ?? '')
            ->setName($data[self::NAME] ?? '')
            ->setDescription($data[self::DESC] ?? '')
            ->setType($data[self::TYPE] ?? '')
            ->setAuthType($data[self::AUTH_TYPE] ?? '')
            ->setUserCount($data[self::USER_COUNT] ?? 0)
            ->setRequestCount($data[self::REQUEST_COUNT] ?? 0);

        return $system;
    }

}