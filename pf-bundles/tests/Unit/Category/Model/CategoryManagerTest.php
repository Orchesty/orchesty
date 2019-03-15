<?php declare(strict_types=1);

namespace Tests\Unit\Category\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Category\Model\CategoryManager;
use Hanaboso\PipesFramework\Category\Repository\CategoryRepository;
use PHPUnit_Framework_MockObject_MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class CategoryManagerTest
 *
 * @package Tests\Unit\Category\Model
 */
final class CategoryManagerTest extends KernelTestCaseAbstract
{

    /**
     * @covers CategoryManager::createCategory()
     * @throws Exception
     */
    public function testCreateCategory(): void
    {

        $parentCategory = new Category();

        $data = [
            'name'   => 'test category 1',
            'parent' => 'parent_category_id',
        ];

        $categoryManager = new CategoryManager($this->getDmlMock($parentCategory));
        $category        = $categoryManager->createCategory($data);

        self::assertEquals($data['name'], $category->getName());
        self::assertEquals($data['parent'], $category->getParent());
    }

    /**
     * @covers CategoryManager::updateCategory()
     * @throws Exception
     */
    public function testUpdateCategory(): void
    {
        $parentCategory = new Category();

        $category = new Category();
        $category->setName('default_name');
        $category->setParent('default_parent');

        $data = [
            'name'   => 'test category 2',
            'parent' => 'parent_category_id',
        ];

        $categoryManager = new CategoryManager($this->getDmlMock($parentCategory));
        $category        = $categoryManager->updateCategory($category, $data);

        self::assertEquals($data['name'], $category->getName());
        self::assertEquals($data['parent'], $category->getParent());
    }

    /**
     * @param Category|null $parentCategory
     *
     * @return DatabaseManagerLocator
     */
    private function getDmlMock(?Category $parentCategory = NULL): DatabaseManagerLocator
    {
        $repository = $this->createMock(CategoryRepository::class);
        $repository->method('find')->willReturn($parentCategory);

        $dm = $this->createPartialMock(DocumentManager::class, ['flush', 'getRepository', 'persist']);
        $dm->method('flush')->willReturn(TRUE);
        $dm->method('persist')->willReturn(TRUE);
        $dm->method('getRepository')->willReturn($repository);

        /** @var PHPUnit_Framework_MockObject_MockObject|DatabaseManagerLocator $dml */
        $dml = $this->createPartialMock(DatabaseManagerLocator::class, ['getDm']);
        $dml->method('getDm')->willReturn($dm);

        return $dml;
    }

}
