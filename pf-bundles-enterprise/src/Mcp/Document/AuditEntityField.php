<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Class AuditEntityField
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Document
 */
#[ODM\EmbeddedDocument]
class AuditEntityField
{

    public const string KEY  = 'key';
    public const string NAME = 'name';

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $key;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $name;

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
     * @return AuditEntityField
     */
    public function setKey(string $key): self
    {
        $this->key = $key;

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
     * @param string $name
     *
     * @return AuditEntityField
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::KEY  => $this->key,
            self::NAME => $this->name,
        ];
    }

}
