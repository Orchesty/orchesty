<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait DescriptionTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait DescriptionTrait
{

    /**
     * @var string|NULL
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @return string|NULL
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|NULL $description
     *
     * @return self
     */
    public function setDescription(?string $description = NULL): self
    {
        $this->description = $description;

        return $this;
    }

}