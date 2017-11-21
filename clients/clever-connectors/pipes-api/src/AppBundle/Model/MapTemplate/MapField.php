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
        $this->type = $type->getValue();
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
     * @param array $data
     *
     * @return MapField|null
     */
    public static function from(array $data): ?MapField
    {
        if (array_key_exists('name', $data) && array_key_exists('type', $data)) {

            $mapField = new MapField($data['name'], new TypeEnum($data['type']));
            if (array_key_exists('items', $data) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $mapField->addItem($item);
                }
            }
        }

        return $mapField ?? NULL;
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