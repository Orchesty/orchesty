<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class Sdk
 *
 * @package Hanaboso\PipesFramework\Configurator\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Configurator\Repository\SdkRepository")
 */
class Sdk
{

    use IdTrait;

    public const ID    = 'id';
    public const KEY   = 'key';
    public const VALUE = 'value';

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $key;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $value;

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
     * @return Sdk
     */
    public function setKey(string $key): Sdk
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return Sdk
     */
    public function setValue(string $value): Sdk
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::ID    => $this->id,
            self::KEY   => $this->key,
            self::VALUE => $this->value,
        ];
    }

}
