<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Database\Document\Category;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\CategoryController;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class CategoryControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(CategoryController::class)]
final class CategoryControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetCategoriesAction(): void
    {
        $this->createCategory();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/CategoryController/getCategoriesRequest.json',
            ['_id' => '123456789', 'parent' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateCategoryAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/CategoryController/createCategoryRequest.json',
            ['_id' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateCategoryAction(): void
    {
        $category = $this->createCategory();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/CategoryController/updateCategoryRequest.json',
            ['_id' => '123456789', 'parent' => '123456789'],
            [':id' => $category->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteCategoryAction(): void
    {
        $category = $this->createCategory();

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/CategoryController/deleteCategoryRequest.json',
            [],
            [':id' => $category->getId()],
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
