<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:27
 */

namespace CcApi\ApiEntity;

/**
 * Class DataLayoutFieldFactory
 *
 * @package CcApi\ApiEntity
 */
class DataLayoutFieldFactory
{

    private const KEY  = 'key';
    private const TYPE = 'type';

    /**
     * @param array $data
     *
     * @return DataLayoutField
     */
    public static function create(array $data): DataLayoutField
    {
        $field = new DataLayoutField();
        $field
            ->setKey($data[self::KEY] ?? '')
            ->setType($data[self::TYPE] ?? '');

        return $field;
    }

}