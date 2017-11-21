<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\DataLayout;

use CleverConnectors\AppBundle\Enum\TypeEnum;

/**
 * Class LayoutField
 *
 * @package CleverConnectors\AppBundle\Model\DataLayout
 */
class LayoutField
{

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $type;

    /**
     * LayoutField constructor.
     *
     * @param string   $key
     * @param TypeEnum $type
     */
    public function __construct(string $key, TypeEnum $type)
    {
        $this->key  = $key;
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
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'key'  => $this->getKey(),
            'type' => $this->getType(),
        ];
    }

}