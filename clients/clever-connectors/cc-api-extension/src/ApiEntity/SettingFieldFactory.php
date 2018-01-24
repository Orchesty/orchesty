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

    private const KEY         = 'key';
    private const TYPE        = 'type';
    private const VALUE       = 'value';
    private const LABEL       = 'label';
    private const REQUIRED    = 'required';
    private const READ_ONLY   = 'read_only';
    private const DISABLED    = 'disabled';
    private const DESCRIPTION = 'description';
    private const CHOICES     = 'choices';

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
            ->setRequired($data[self::REQUIRED] ?? FALSE)
            ->setReadOnly($data[self::READ_ONLY] ?? FALSE)
            ->setDisabled($data[self::DISABLED] ?? FALSE)
            ->setDescription($data[self::DESCRIPTION] ?? '')
            ->setChoices($data[self::CHOICES] ?? []);

        return $field;
    }

}