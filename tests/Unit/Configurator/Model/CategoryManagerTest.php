<?php declare(strict_types=1);

namespace Tests\Unit\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\CommonsBundle\Database\Document\Category;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Database\Repository\CategoryRepository;
use Hanaboso\PipesFramework\Configurator\Model\CategoryManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\KernelTestCaseAbstract;

/**
 * Class CategoryManagerTest
 *
 * @package Tests\Unit\Configurator\Model
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
     * @throws Exception
     */
    private function getDmlMock(?Category $parentCategory = NULL): DatabaseManagerLocator
    {
        $repository = self::createMock(CategoryRepository::class);
        $repository->method('find')->willReturn($parentCategory);

        $dm = self::createPartialMock(DocumentManager::class, ['flush', 'getRepository', 'persist']);
        $dm->method('flush')->willReturn(TRUE);
        $dm->method('persist')->willReturn(TRUE);
        $dm->method('getRepository')->willReturn($repository);

        /** @var MockObject|DatabaseManagerLocator $dml */
        $dml = self::createPartialMock(DatabaseManagerLocator::class, ['getDm']);
        $dml->method('getDm')->willReturn($dm);

        return $dml;
    }

}
