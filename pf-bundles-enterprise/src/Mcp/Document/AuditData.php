<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class AuditData
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Document
 */
#[ODM\Document(repositoryClass: 'Hanaboso\PipesFrameworkEnterprise\Mcp\Repository\AuditDataRepository')]
#[ODM\Index(
    keys: ['entity' => 'asc', 'fields.key' => 'asc', 'fields.value' => 'asc', 'user' => 'asc'],
    name: 'IK_audit_data_entity_fields_user',
)]
class AuditData
{

    use IdTrait;

    public const string ID     = 'id';
    public const string USER   = 'user';
    public const string ENTITY = 'entity';
    public const string FIELDS = 'fields';

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $user;

    /**
     * @var string
     */
    #[ODM\Field(type: 'string')]
    private string $entity;

    /**
     * @var Collection<int, AuditDataField>
     */
    #[ODM\EmbedMany(targetDocument: AuditDataField::class)]
    private Collection $fields;

    /**
     * AuditData constructor.
     */
    public function __construct()
    {
        $this->fields = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * @param string $user
     *
     * @return AuditData
     */
    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntity(): string
    {
        return $this->entity;
    }

    /**
     * @param string $entity
     *
     * @return AuditData
     */
    public function setEntity(string $entity): self
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return Collection<int, AuditDataField>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * @param Collection<int, AuditDataField> $fields
     *
     * @return AuditData
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
            self::ENTITY => $this->entity,
            self::FIELDS => $this->fields->map(static fn(AuditDataField $field) => $field->toArray())->toArray(),
            self::ID     => $this->id,
            self::USER   => $this->user,
        ];
    }

}
