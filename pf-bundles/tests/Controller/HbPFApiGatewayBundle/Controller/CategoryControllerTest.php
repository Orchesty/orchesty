<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\Database\Document\Category;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class CategoryControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\CategoryController
 */
final class CategoryControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\CategoryController::getCategoriesAction
     */
    public function testGetCategoriesAction(): void
    {
        $this->createCategory();

        $this->assertResponse(
            __DIR__ . '/data/CategoryController/getCategoriesRequest.json',
            ['_id' => '123456789', 'parent' => '123456789']
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\CategoryController::createCategoryAction
     */
    public function testCreateCategoryAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/CategoryController/createCategoryRequest.json',
            ['_id' => '123456789']
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\CategoryController::updateCategoryAction
     */
    public function testUpdateCategoryAction(): void
    {
        $category = $this->createCategory();

        $this->assertResponse(
            __DIR__ . '/data/CategoryController/updateCategoryRequest.json',
            ['_id' => '123456789', 'parent' => '123456789'],
            [':id' => $category->getId()]
        );
    }

    /**
     * @throws Exception
     *
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\CategoryController::deleteCategoryAction
     */
    public function testDeleteCategoryAction(): void
    {
        $category = $this->createCategory();

        $this->assertResponse(
            __DIR__ . '/data/CategoryController/deleteCategoryRequest.json',
            [],
            [':id' => $category->getId()]
        );
    }

    /**
     * @return Category
     * @throws Exception
     */
    private function createCategory(): Category
    {
        $category = (new Category())->setName('Parent Category');
        $this->pfd($category);

        $innerCategory = (new Category())->setName('Child Category')->setParent($category->getId());
        $this->pfd($innerCategory);

        return $innerCategory;
    }

}
