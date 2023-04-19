<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocatorInterface;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\PipesFramework\Configurator\Model\CategoryManager;
use Hanaboso\PipesFramework\Database\Document\Category;

/**
 * Class CategoryHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class CategoryHandler
{

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * CategoryHandler constructor.
     *
     * @param DatabaseManagerLocatorInterface $dml
     * @param CategoryManager                 $categoryManager
     */
    public function __construct(DatabaseManagerLocatorInterface $dml, private CategoryManager $categoryManager)
    {
        /** @var DocumentManager $dm */
        $dm       = $dml->getDm();
        $this->dm = $dm;
    }

    /**
     * @return mixed[]
     */
    public function getCategories(): array
    {
        /** @var Category[] $categories */
        $categories = $this->dm->getRepository(Category::class)->findAll();
        $data       = ['items' => []];

        foreach ($categories as $category) {
            $data['items'][] = $this->getCategoryData($category);
        }

        $data['total']  = count($categories);
        $data['limit']  = NULL;
        $data['count']  = count($categories);
        $data['offset'] = 0;

        return $data;
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws CategoryException
     * @throws MongoDBException
     */
    public function createCategory(array $data): array
    {
        $category = $this->categoryManager->createCategory($data);

        return $this->getCategoryData($category);
    }

    /**
     * @param string  $id
     * @param mixed[] $data
     *
     * @return mixed[]
     * @throws CategoryException
     * @throws MongoDBException
     */
    public function updateCategory(string $id, array $data): array
    {
        $category = $this->getCategory($id);

        $this->categoryManager->updateCategory($category, $data);

        return $this->getCategoryData($category);
    }

    /**
     * @param string $id
     *
     * @return mixed[]
     * @throws CategoryException
     * @throws MongoDBException
     */
    public function deleteCategory(string $id): array
    {
        $category = $this->getCategory($id);

        $this->categoryManager->deleteCategory($category);

        return [];
    }

    /**
     * @param string $id
     *
     * @return Category
     * @throws CategoryException
     */
    private function getCategory(string $id): Category
    {
        /** @var Category|null $category */
        $category = $this->dm->getRepository(Category::class)->find($id);
        if (empty($category)) {
            throw new CategoryException(sprintf('Category [%s] not found', $id), CategoryException::CATEGORY_NOT_FOUND);
        }

        return $category;
    }

    /**
     * @param Category $category
     *
     * @return mixed[]
     */
    private function getCategoryData(Category $category): array
    {
        return [
            'name'   => $category->getName(),
            'parent' => $category->getParent(),
            '_id'    => $category->getId(),
        ];
    }

}
