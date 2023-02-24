<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Document;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Category;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class CategoryTest
 *
 * @package PipesFrameworkTests\Integration\Database\Document
 */
final class CategoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Database\Document\Category
     * @covers \Hanaboso\PipesFramework\Database\Document\Category::getName
     * @covers \Hanaboso\PipesFramework\Database\Document\Category::setName
     * @covers \Hanaboso\PipesFramework\Database\Document\Category::getParent
     * @covers \Hanaboso\PipesFramework\Database\Document\Category::setParent
     *
     * @throws Exception
     */
    public function testCategory(): void
    {
        $category = (new Category())
            ->setName('name')
            ->setParent('parent');
        $this->pfd($category);

        self::assertEquals('name', $category->getName());
        self::assertEquals('parent', $category->getParent());
    }

}
