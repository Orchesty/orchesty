<?php declare(strict_types=1);

namespace CleverCore\Commons\Traits;

/**
 * Trait LevelTrait
 *
 * @package CleverCore\Commons\Traits
 */
trait LevelTrait
{

    /**
     * @var int
     *
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     */
    private $lvl;

    /**
     * @return int
     */
    public function getLvl(): int
    {
        return $this->lvl;
    }

    /**
     * @param int $lvl
     */
    public function setLvl(int $lvl): void
    {
        $this->lvl = $lvl;
    }

}