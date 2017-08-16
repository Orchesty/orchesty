<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Traits;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * Trait IdTrait
 *
 * @package Hanaboso\PipesFramework\Commons\Traits
 */
trait IdTrait
{

    /**
     * @var string
     *
     * @ODM\Id()
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