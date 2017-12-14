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
    private const CUSTOM_FORM    = 'custom_form';
    private const ACTIONS        = 'actions';
    private const DATA_LAYOUTS   = 'data_layouts';
    private const MAP_TEMPLATES  = 'map_templates';

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
            ->setSynchronized($data[self::SYNCHRONIZED] ?? FALSE)
            ->setCustomForm($data[self::CUSTOM_FORM] ?? []);

        if (array_key_exists(self::SETTING_FIELDS, $data)) {
            foreach ($data[self::SETTING_FIELDS] as $field) {
                $system->addSettingField(SettingFieldFactory::create($field));
            }
        }

        $system->setActions($data[self::ACTIONS] ?? []);

        if (array_key_exists(self::DATA_LAYOUTS, $data)) {
            foreach ($data[self::DATA_LAYOUTS] as $item) {
                $system->addDataLayout(DataLayoutFactory::create($item));
            }
        }

        if (array_key_exists(self::MAP_TEMPLATES, $data)) {
            foreach ($data[self::MAP_TEMPLATES] as $item) {
                $system->addMapTemplate(MapTemplateFactory::create($item));
            }
        }

        return $system;
    }

}