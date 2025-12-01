<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class AuditEntity
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Document
 */
#[ODM\Document(repositoryClass: 'Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditEntityRepository')]
#[ODM\Index(keys: ['key' => 'asc'], name: 'UK_audit_entity_key', unique: TRUE)]
class AuditEntity
{

    use IdTrait;

    public const string ID     = 'id';
    public const string KEY    = 'key';
    public const string NAME   = 'name';
    public const string FIELDS = 'fields';

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
     * @var Collection<int, AuditEntityField>
     */
    #[ODM\EmbedMany(targetDocument: AuditEntityField::class)]
    private Collection $fields;

    /**
     * AuditEntity constructor.
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
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
     * @return AuditEntity
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
     * @return AuditEntity
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, AuditEntityField>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * @param Collection<int, AuditEntityField> $fields
     *
     * @return AuditEntity
     */
    public function setFields(Collection $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function toArray(): array
    {
        return [
            self::FIELDS => $this->fields->map(static fn(AuditEntityField $field) => $field->toArray())->toArray(),
            self::ID     => $this->id,
            self::KEY    => $this->key,
            self::NAME   => $this->name,
        ];
    }

}
