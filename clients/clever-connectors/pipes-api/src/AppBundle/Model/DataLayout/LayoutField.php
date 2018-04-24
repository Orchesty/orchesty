<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\DataLayout;

use CleverConnectors\AppBundle\Enum\TypeEnum;
use Hanaboso\CommonsBundle\Exception\EnumException;

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
     * @param string $key
     * @param string $type
     *
     * @throws EnumException
     */
    public function __construct(string $key, string $type)
    {
        $this->key  = $key;
        $this->type = TypeEnum::isValid($type);
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