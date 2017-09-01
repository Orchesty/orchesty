<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Traits\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait IdTrait
 *
 * @package Hanaboso\PipesFramework\Commons\Traits\Entity
 */
trait IdTrait
{

    /**
     * @var string
     *
     * @ORM\Column(type="id")
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