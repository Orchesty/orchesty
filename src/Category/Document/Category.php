<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/27/17
 * Time: 11:07 AM
 */

namespace Hanaboso\PipesFramework\Category\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Index;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;


/**
 * Class Category
 *
 * @package Hanaboso\PipesFramework\Category\Document
 *
 * @ODM\Document(repositoryClass="Hanaboso\PipesFramework\Category\Repository\CategoryRepository")
 */
class Category
{

    use IdTrait;

    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     */
    protected $name;


    /**
     * @var string
     *
     * @MongoDB\Field(type="string")
     * @Index()
     */
    protected $parent;

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
     * @return string
     */
    public function getParent(): string
    {
        return $this->parent;
    }

    /**
     * @param string $parent
     *
     * @return Category
     */
    public function setParent(string $parent): Category
    {
        $this->parent = $parent;

        return $this;
    }

}
