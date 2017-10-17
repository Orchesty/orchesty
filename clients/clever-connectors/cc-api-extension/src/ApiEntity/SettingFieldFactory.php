<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 4:43 PM
 */

namespace CcApi\ApiEntity;

/**
 * Class SettingFieldFactory
 *
 * @package CcApi\ApiEntity
 */
class SettingFieldFactory
{

    private const KEY      = 'key';
    private const TYPE     = 'type';
    private const VALUE    = 'value';
    private const LABEL    = 'label';
    private const REQUIRED = 'required';

    /**
     * @param array $data
     *
     * @return SettingField
     */
    public static function create(array $data): SettingField
    {
        $field = new SettingField();

        $field
            ->setKey($data[self::KEY] ?? '')
            ->setType($data[self::TYPE] ?? '')
            ->setValue($data[self::VALUE] ?? '')
            ->setLabel($data[self::LABEL] ?? '')
            ->setRequired($data[self::REQUIRED] ?? FALSE);

        return $field;
    }

}