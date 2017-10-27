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

    private const KEY       = 'key';
    private const NAME      = 'name';
    private const DESC      = 'description';
    private const TYPE      = 'type';
    private const AUTH_TYPE = 'auth_type';

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
            ->setAuthType($data[self::AUTH_TYPE] ?? '');

        return $system;
    }

}