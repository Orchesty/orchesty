<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\PipesFramework\Configurator\Model\CategoryManager;
use Hanaboso\PipesFramework\Database\Document\Category;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler;
use LogicException;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class CategoryControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 */
final class CategoryControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::getCategoriesAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::getCategories
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::getCategoryData
     *
     * @throws Exception
     */
    public function testGetCategories(): void
    {
        $this->createCategories(4);
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Category/getCategoriesRequest.json',
            [
                '_id' => '5e3293c74f674f452942a9d4',
                'parent' => '5e32945ec6117b57df219493',
            ],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::createCategoryAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::createCategory
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::getCategoryData
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::createCategory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::setCategoryData
     *
     * @throws Exception
     */
    public function testCreateTopology(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Category/createCategoryRequest.json',
            ['_id' => '5e3294f6486bd447291eb8e3'],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::createCategoryAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::createCategory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::createCategory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::setCategoryData
     *
     * @throws Exception
     */
    public function testCreateCategoryErr(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/Category/createCategoryErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::updateCategoryAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::updateCategory
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::getCategory
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::getCategoryData
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::updateCategory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::setCategoryData
     *
     * @throws Exception
     */
    public function testUpdateCategory(): void
    {
        $categories = $this->createCategories(2);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Category/updateCategoryRequest.json',
            ['_id' => '5e3297eee83e1850c8387dc4', 'parent' => '5e3297eee83e1850c8387dc3'],
            [':id' => $categories[1]->getId()],
            ['parent' => $categories[0]->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::updateCategoryAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::updateCategory
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::getCategory
     *
     * @throws Exception
     */
    public function testUpdateCategoryNotFound(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Category/updateCategoryNotFoundRequest.json',
            [],
            [':id' => 999],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::updateCategoryAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::updateCategory
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::getCategory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::updateCategory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::setCategoryData
     *
     * @throws Exception
     */
    public function testUpdateCategoryErr(): void
    {
        $categories = $this->createCategories(2);

        $manager = self::createPartialMock(CategoryManager::class, ['updateCategory']);
        $manager->expects(self::any())->method('updateCategory')->willThrowException(new MongoDBException());
        self::getContainer()->set('hbpf.configurator.manager.category', $manager);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Category/updateCategoryErrRequest.json',
            [],
            [':id' => $categories[1]->getId()],
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::deleteCategoryAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::deleteCategory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::deleteCategory
     *
     * @throws Exception
     */
    public function testDeleteCategory(): void
    {
        $categories = $this->createCategories();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/Category/deleteCategoryRequest.json',
            [],
            [':id' => $categories[0]->getId()],
        );
    }

    /**
     * @covers       \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\CategoryController::deleteCategoryAction
     * @covers       \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\CategoryHandler::deleteCategory
     *
     * @dataProvider categoryErrorProvider
     *
     * @param Exception $exception
     *
     * @throws Exception
     */
    public function testDeleteCategoryNotFound(Exception $exception): void
    {
        $han = $this->createPartialMock(CategoryHandler::class, ['deleteCategory']);
        $han->method('deleteCategory')->willThrowException($exception);
        self::getContainer()->set('hbpf.handler.category', $han);

        switch ($exception->getCode()) {
            case CategoryException::CATEGORY_NOT_FOUND:
                $this->assertResponseLogged(
                    $this->jwt,
                    __DIR__ . '/data/Category/deleteCategory404Request.json',
                    ['error_code' => 404],
                );

                break;
            case CategoryException::CATEGORY_USED:
                $this->assertResponseLogged(
                    $this->jwt,
                    __DIR__ . '/data/Category/deleteCategory400Request.json',
                    ['error_code' => 400],
                );

                break;
            default:
                $this->assertResponseLogged(
                    $this->jwt,
                    __DIR__ . '/data/Category/deleteCategory500Request.json',
                    ['error_code' => 500],
                );
        }
    }

    /**
     * @return mixed[]
     */
    public function categoryErrorProvider(): array
    {
        return [
            [new CategoryException(code: CategoryException::CATEGORY_USED)],
            [new CategoryException(code: CategoryException::CATEGORY_NOT_FOUND)],
            [new LogicException()],
        ];
    }

    /**
     * @param int $count
     *
     * @return Category[]
     * @throws Exception
     */
    private function createCategories(int $count = 1): array
    {
        $categories = [];
        for ($i = 1; $i <= $count; $i++) {
            $category = (new Category())
                ->setName(sprintf('name %s', $i))
                ->setParent($i > 2 ? $categories[0]->getId() : NULL);
            $this->pfd($category);

            $categories[] = $category;
        }

        return $categories;
    }

}
