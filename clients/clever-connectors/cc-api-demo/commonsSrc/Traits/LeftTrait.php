<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait LeftTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait LeftTrait
{

    /**
     * @var int
     *
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @return mixed
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * @param mixed $lft
     */
    public function setLft($lft): void
    {
        $this->lft = $lft;
    }

}