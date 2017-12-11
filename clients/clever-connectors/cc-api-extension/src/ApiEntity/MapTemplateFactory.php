<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 14:09
 */

namespace CcApi\ApiEntity;

/**
 * Class MapTemplateFactory
 *
 * @package CcApi\ApiEntity
 */
class MapTemplateFactory
{

    private const ID        = 'id';
    private const ACTION    = 'action';
    private const DIRECTION = 'direction';
    private const FIELDS    = 'fields';

    /**
     * @param array $data
     *
     * @return MapTemplate
     */
    public static function create(array $data): MapTemplate
    {
        $mapTemplate = new MapTemplate();
        $mapTemplate
            ->setId($data[self::ID] ?? '')
            ->setAction($data[self::ACTION])
            ->setDirection($data[self::DIRECTION]);

        foreach ($data[self::FIELDS] as $value) {
            $mapTemplate->addField(MapTemplateFieldFactory::create($value));
        }

        return $mapTemplate;
    }

}