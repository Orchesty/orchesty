<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait NameTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait NameTrait
{

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     */
    protected $name;

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
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

}