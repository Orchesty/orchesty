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

    private const KEY   = 'key';
    private const NAME  = 'name';
    private const TYPE  = 'type';
    private const ITEMS = 'items';

    /**
     * @var string
     */
    private $key;

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
    private $items = [];

    /**
     * MapField constructor.
     *
     * @param string   $key
     * @param TypeEnum $type
     * @param string   $name
     */
    public function __construct(string $key, TypeEnum $type, string $name = '')
    {
        $this->key  = $key;
        $this->name = $name;
        $this->type = $type->getValue();
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return MapField
     */
    public function setKey(string $key): MapField
    {
        $this->key = $key;

        return $this;
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
        if (array_key_exists(self::KEY, $data) && array_key_exists(self::TYPE, $data)) {

            $mapField = new MapField($data[self::KEY], new TypeEnum($data[self::TYPE]), $data[self::NAME] ?? '');
            if (array_key_exists(self::ITEMS, $data) && is_array($data[self::ITEMS])) {
                foreach ($data[self::ITEMS] as $item) {
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
            self::KEY   => $this->getKey(),
            self::NAME  => $this->getName(),
            self::TYPE  => $this->getType(),
            self::ITEMS => $this->getItems(),
        ];
    }

}