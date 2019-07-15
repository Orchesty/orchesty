<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConfiguratorBundle\Controller;

use Hanaboso\CommonsBundle\Database\Document\Category;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Tests\ControllerTestCaseAbstract;

/**
 * Class CategoryControllerTest
 *
 * @package Tests\Controller\HbPFConfiguratorBundle\Controller
 */
final class CategoryControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers CategoryController::getCategoriesAction()
     */
    public function testGetCategories(): void
    {
        $this->createCategories(4);

        $response = $this->sendGet('/api/categories');

        self::assertEquals(200, $response->status);
        self::assertEquals(0, $response->content->offset);
        self::assertNull($response->content->limit);
        self::assertEquals(4, $response->content->count);
        self::assertEquals(4, $response->content->total);

        self::assertCount(4, $response->content->items);
    }

    /**
     * @covers CategoryController::createCategoryAction()
     */
    public function testCreateTopology(): void
    {
        $response = $this->sendPost('/api/categories', [
            'name' => 'Test category',
        ]);

        self::assertEquals(200, $response->status);
        self::assertEquals('Test category', $response->content->name);
        self::assertNull($response->content->parent);
    }

    /**
     * @covers CategoryController::updateCategoryAction()
     */
    public function testUpdateCategory(): void
    {
        $categories = $this->createCategories(2);

        $response = $this->sendPut(sprintf('/api/categories/%s', $categories[1]->getId()), [
            'name'   => 'edited',
            'parent' => $categories[0]->getId(),
        ]);

        self::assertEquals(200, $response->status);
        self::assertEquals('edited', $response->content->name);
        self::assertEquals($categories[0]->getId(), $response->content->parent);
        self::assertEquals($categories[1]->getId(), $response->content->_id);
    }

    /**
     * @covers CategoryController::updateCategoryAction()
     */
    public function testUpdateCategoryNotFound(): void
    {
        $response = $this->sendPut(sprintf('/api/categories/999'), [
            'name' => 'Category 2',
        ]);
        $content  = $response->content;

        self::assertEquals(400, $response->status);
        self::assertEquals(CategoryException::class, $content->type);
        self::assertEquals('ERROR', $content->status);
        self::assertEquals(2001, $content->errorCode);
    }

    /**
     * @covers CategoryController::deleteCategoryAction()
     */
    public function testDeleteCategory(): void
    {
        $categories = $this->createCategories(1);

        $response = $this->sendDelete(sprintf('/api/categories/%s', $categories[0]->getId()));

        self::assertEquals(200, $response->status);
    }

    /**
     * @param int $count
     *
     * @return Category[]
     */
    private function createCategories(int $count = 1): array
    {
        $categories = [];
        for ($i = 1; $i <= $count; $i++) {
            $category = (new Category())
                ->setName(sprintf('name %s', $i))
                ->setParent($i > 2 ? $categories[0]->getId() : NULL);
            $this->persistAndFlush($category);

            $categories[] = $category;
        }

        return $categories;
    }

}
