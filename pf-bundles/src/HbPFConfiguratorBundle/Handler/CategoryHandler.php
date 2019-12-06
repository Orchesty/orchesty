<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Document\Category;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocatorInterface;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\PipesFramework\Configurator\Model\CategoryManager;

/**
 * Class CategoryHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
class CategoryHandler
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var CategoryManager
     */
    private $categoryManager;

    /**
     * CategoryHandler constructor.
     *
     * @param DatabaseManagerLocatorInterface $dml
     * @param CategoryManager                 $categoryManager
     */
    public function __construct(DatabaseManagerLocatorInterface $dml, CategoryManager $categoryManager)
    {
        /** @var DocumentManager $dm */
        $dm                    = $dml->getDm();
        $this->dm              = $dm;
        $this->categoryManager = $categoryManager;
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
     * @throws LockException
     * @throws MappingException
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
     * @throws LockException
     * @throws MappingException
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
     * @throws MappingException
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
     * @throws LockException
     * @throws MappingException
     */
    private function getCategory(string $id): Category
    {
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
            '_id'    => $category->getId(),
            'name'   => $category->getName(),
            'parent' => $category->getParent(),
        ];
    }

}
