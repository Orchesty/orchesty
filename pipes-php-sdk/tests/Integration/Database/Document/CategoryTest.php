<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Database\Document;

use Hanaboso\PipesPhpSdk\Database\Document\Category;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class CategoryTest
 *
 * @package PipesPhpSdkTests\Integration\Database\Document
 */
final class CategoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Category
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Category::getName
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Category::setName
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Category::getParent
     * @covers \Hanaboso\PipesPhpSdk\Database\Document\Category::setParent
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
