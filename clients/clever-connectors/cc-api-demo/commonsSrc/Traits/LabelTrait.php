<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait LabelTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait LabelTrait
{

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $label;

    /**
     * @return null|string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param null|string $label
     *
     * @return self
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

}