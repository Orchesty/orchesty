<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:20
 */

namespace CcApi\ApiEntity;

/**
 * Class DataLayoutFactory
 *
 * @package CcApi\ApiEntity
 */
class DataLayoutFactory
{

    private const ID     = '_id';
    private const ACTION = 'action';
    private const FIELDS = 'fields';

    /**
     * @param array $data
     *
     * @return DataLayout
     */
    public static function create(array $data): DataLayout
    {
        $dataLayout = new DataLayout();
        $dataLayout
            ->setId($data[self::ID] ?? '')
            ->setAction($data[self::ACTION] ?? '');

        if (isset($data[self::FIELDS])) {
            foreach ($data[self::FIELDS] as $value) {
                $dataLayout->addField(DataLayoutFieldFactory::create($value));
            }
        }

        return $dataLayout;
    }

}