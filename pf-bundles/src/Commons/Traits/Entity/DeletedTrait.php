<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Traits\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait DeletedTrait
 *
 * @package Hanaboso\PipesFramework\Commons\Traits\Entity
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