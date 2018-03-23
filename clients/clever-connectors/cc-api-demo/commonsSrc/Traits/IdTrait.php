<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait IdTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait IdTrait
{

    /**
     * @var string
     *
     * @ORM\Column(type="bigint", nullable=false, options={"unsigned":true})
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

}