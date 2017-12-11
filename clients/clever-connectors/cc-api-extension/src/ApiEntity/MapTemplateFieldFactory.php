<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:14
 */

namespace CcApi\ApiEntity;

/**
 * Class MapTemplateFieldFactory
 *
 * @package CcApi\ApiEntity
 */
class MapTemplateFieldFactory
{

    private const NAME  = 'name';
    private const TYPE  = 'type';
    private const ITEMS = 'items';

    /**
     * @param array $data
     *
     * @return MapTemplateField
     */
    public static function create(array $data): MapTemplateField
    {
        $field = new MapTemplateField();
        $field
            ->setName($data[self::NAME] ?? '')
            ->setType($data[self::TYPE] ?? '')
            ->setItems($data[self::ITEMS] ?? []);

        return $field;
    }

}