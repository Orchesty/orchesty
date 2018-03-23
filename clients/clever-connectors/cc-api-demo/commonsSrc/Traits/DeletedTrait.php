<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait DeletedTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait DeletedTrait
{

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $deleted = FALSE;

    /**
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     *
     * @return self
     */
    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;

        return $this;
    }

}