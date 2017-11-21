<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\MapTemplate;

use CleverConnectors\AppBundle\Enum\TypeEnum;

/**
 * Class MapField
 *
 * @package CleverConnectors\AppBundle\Model\MapTemplate
 */
class MapField
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
     * @var string[]|array
     */
    private $items;

    /**
     * MapField constructor.
     *
     * @param string   $name
     * @param TypeEnum $type
     */
    public function __construct(string $name, TypeEnum $type)
    {
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * @param string $name
     *
     * @return MapField
     */
    public function setName(string $name): MapField
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param TypeEnum $type
     *
     * @return MapField
     */
    public function setType(TypeEnum $type): MapField
    {
        $this->type = $type->getValue();

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
     * @param string $item
     *
     * @return MapField
     */
    public function addItem(string $item): MapField
    {
        $this->items[] = $item;

        return $this;
    }

    /**
     * @return string[]|array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'  => $this->getName(),
            'type'  => $this->getType(),
            'items' => $this->getItems(),
        ];
    }

}