<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\DataLayout;

use Hanaboso\PipesFramework\Commons\Enum\TypeEnum;

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
     * @var TypeEnum
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
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return TypeEnum
     */
    public function getType(): TypeEnum
    {
        return $this->type;
    }

}