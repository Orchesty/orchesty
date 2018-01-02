<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 13:55
 */

namespace CcApi\ApiEntity;

/**
 * Class MapTemplate
 *
 * @package CcApi\ApiEntity
 */
class MapTemplate
{

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $direction;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return MapTemplate
     */
    public function setId(string $id): MapTemplate
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return MapTemplate
     */
    public function setAction(string $action): MapTemplate
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string
     */
    public function getDirection(): string
    {
        return $this->direction;
    }

    /**
     * @param string $direction
     *
     * @return MapTemplate
     */
    public function setDirection(string $direction): MapTemplate
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * @return array|MapTemplateField[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param MapTemplateField $mapTemplateField
     *
     * @return MapTemplate
     */
    public function addField(MapTemplateField $mapTemplateField): MapTemplate
    {
        $this->fields[] = $mapTemplateField;

        return $this;
    }

}