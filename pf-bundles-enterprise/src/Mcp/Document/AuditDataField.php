<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class AuditDataField
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Document
 */
#[ODM\EmbeddedDocument]
class AuditDataField
{

    public const string KEY   = 'key';
    public const string VALUE = 'value';

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $key;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $value;

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
     * @return AuditDataField
     */
    public function setKey(string $key): self
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
     * @return AuditDataField
     */
    public function setValue(string $value): self
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
            self::KEY   => $this->key,
            self::VALUE => $this->value,
        ];
    }

}
