<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 4:37 PM
 */

namespace CcApi\ApiEntity;

/**
 * Class UserSystemFactory
 *
 * @package CcApi\ApiEntity
 */
class UserSystemFactory
{

    private const KEY            = 'key';
    private const NAME           = 'name';
    private const DESC           = 'description';
    private const TYPE           = 'type';
    private const TOKEN          = 'token';
    private const AUTH_TYPE      = 'auth_type';
    private const AUTHORIZED     = 'authorized';
    private const SYNCHRONIZED   = 'synchronized';
    private const SETTING_FIELDS = 'setting_fields';

    /**
     * @param array $data
     *
     * @return UserSystem
     */
    public static function create(array $data): UserSystem
    {
        $system = new UserSystem();

        $system
            ->setKey($data[self::KEY] ?? '')
            ->setName($data[self::NAME] ?? '')
            ->setDescription($data[self::DESC] ?? '')
            ->setType($data[self::TYPE] ?? '')
            ->setToken($data[self::TOKEN] ?? '')
            ->setAuthType($data[self::AUTH_TYPE] ?? '')
            ->setAuthorized($data[self::AUTHORIZED] ?? FALSE)
            ->setSynchronized($data[self::SYNCHRONIZED] ?? FALSE);

        if (array_key_exists(self::SETTING_FIELDS, $data)) {
            foreach ($data[self::SETTING_FIELDS] as $field) {
                $system->addSettingField(SettingFieldFactory::create($field));
            }
        }

        return $system;
    }

}