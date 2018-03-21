<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait RightTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait RightTrait
{

    /**
     * @var int
     *
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @return mixed
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * @param mixed $rgt
     */
    public function setRgt($rgt): void
    {
        $this->rgt = $rgt;
    }

}