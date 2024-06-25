<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Document;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Category;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class CategoryTest
 *
 * @package PipesFrameworkTests\Integration\Database\Document
 */
#[CoversClass(Category::class)]
final class CategoryTest extends DatabaseTestCaseAbstract
{

    /**
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
