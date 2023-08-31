<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Database\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class Category
 *
 * @package Hanaboso\PipesFramework\Database\Document
 *
 * @ODM\Document(
 *     repositoryClass="Hanaboso\PipesFramework\Database\Repository\CategoryRepository",
 *     indexes={
 *         @ODM\Index(keys={"name": "asc", "parent": "asc"}, unique=true)
 *     }
 * )
 */
class Category
{

    use IdTrait;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    protected string $name;

    /**
     * @var string|null
     *
     * @ODM\Field(type="string")
     * @ODM\Index()
     */
    protected ?string $parent = NULL;

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
     * @return Category
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getParent(): ?string
    {
        return $this->parent;
    }

    /**
     * @param string|null $parent
     *
     * @return Category
     */
    public function setParent(?string $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

}
