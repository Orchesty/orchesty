<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/27/17
 * Time: 4:31 PM
 */

namespace Tests\Unit\Category\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Category\Model\CategoryManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class CategoryManagerTest
 *
 * @package Tests\Unit\Category\Model
 */
class CategoryManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers CategoryManager::createCategory()
     */
    public function createCategoryTest(): void
    {
        $data = [
          'name' => 'test category 1',
          'parent' => 'parent_category_id'
        ];

        $categoryManager = new CategoryManager($this->getDmlMock());
        $category = $categoryManager->createCategory($data);

        self::assertEquals($data['name'], $category->getName());
        self::assertEquals($data['parent'], $category->getParent());
    }

    /**
     * @covers CategoryManager::updateCategory()
     */
    public function updateCategoryTest(): void
    {
        $category = new Category();
        $category->setName('default_name');
        $category->setParent('default_parent');

        $data = [
          'name' => 'test category 2',
          'parent' => 'parent_category_id'
        ];

        $categoryManager = new CategoryManager($this->getDmlMock());
        $category = $categoryManager->updateCategory($category, $data);

        self::assertEquals($data['name'], $category->getName());
        self::assertEquals($data['parent'], $category->getParent());
    }



    /**
     * @return DatabaseManagerLocator
     */
    private function getDmlMock(): DatabaseManagerLocator
    {
        $dm = $this->createPartialMock(DocumentManager::class, ['flush']);
        $dm->method('flush')->willReturn(TRUE);

        /** @var PHPUnit_Framework_MockObject_MockObject|DatabaseManagerLocator $dml */
        $dml = $this->createPartialMock(DatabaseManagerLocator::class, ['getDm']);
        $dml->method('getDm')->willReturn($dm);

        return $dml;
    }
}