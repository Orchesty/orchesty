<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Document\Attributes;

/**
 * Class IdTrait
 *
 * @package Hanaboso\PipesFramework\Commons\Document\Attributes
 */
class IdTrait
{

    /**
     * @var string
     *
     * @MongoDB\Id
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