<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 11.12.17
 * Time: 13:56
 */

namespace CcApi\ApiEntity;

/**
 * Class MapTemplateField
 *
 * @package CcApi\ApiEntity
 */
class MapTemplateField
{

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array|string[]
     */
    private $items = [];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return MapTemplateField
     */
    public function setName(string $name): MapTemplateField
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return MapTemplateField
     */
    public function setType(string $type): MapTemplateField
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array|string[] $items
     *
     * @return MapTemplateField
     */
    public function setItems(array $items): MapTemplateField
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param string $item
     *
     * @return MapTemplateField
     */
    public function addItem(string $item): MapTemplateField
    {
        $this->items[] = $item;

        return $this;
    }

}