<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\CommonsBundle\Database\Traits\Document\IdTrait;

/**
 * Class Category
 *
 * @package Hanaboso\PipesPhpSdk\Database\Document
 *
 * @ODM\Document(
 *     repositoryClass="Hanaboso\PipesPhpSdk\Database\Repository\CategoryRepository",
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
    public function setName(string $name): Category
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
    public function setParent(?string $parent): Category
    {
        $this->parent = $parent;

        return $this;
    }

}
