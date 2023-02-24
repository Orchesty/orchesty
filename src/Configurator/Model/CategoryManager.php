<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\PipesFramework\Database\Document\Category;
use Hanaboso\PipesFramework\Database\Document\Topology;

/**
 * Class CategoryManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final class CategoryManager
{

    /**
     * @var DocumentManager
     */
    private DocumentManager $dm;

    /**
     * CategoryManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     */
    function __construct(DatabaseManagerLocator $dml)
    {
        /** @var DocumentManager $dm */
        $dm       = $dml->getDm();
        $this->dm = $dm;
    }

    /**
     * @param mixed[] $data
     *
     * @return Category
     * @throws CategoryException
     * @throws MongoDBException
     */
    public function createCategory(array $data): Category
    {
        $category = $this->setCategoryData(new Category(), $data);
        $this->dm->persist($category);
        $this->dm->flush();

        return $category;
    }

    /**
     * @param Category $category
     * @param mixed[]  $data
     *
     * @return Category
     * @throws CategoryException
     * @throws MongoDBException
     */
    public function updateCategory(Category $category, array $data): Category
    {
        $this->setCategoryData($category, $data);
        $this->dm->flush();

        return $category;
    }

    /**
     * @param Category $category
     *
     * @throws CategoryException
     * @throws MongoDBException
     */
    public function deleteCategory(Category $category): void
    {
        $topologyRepository = $this->dm->getRepository(Topology::class);
        $topologies         = $topologyRepository->getTopologiesByCategory($category);
        if (count($topologies) == 0) {
            $categoryRepository = $this->dm->getRepository(Category::class);
            $categoryRepository->childrenLevelUp($category);
            $this->dm->remove($category);
            $this->dm->flush();
        } else {
            throw new CategoryException(
                'Category used by topology cannot be remove.',
                CategoryException::CATEGORY_USED,
            );
        }
    }

    /**
     * @param Category $category
     * @param mixed[]  $data
     *
     * @return Category
     * @throws CategoryException
     */
    private function setCategoryData(Category $category, array $data): Category
    {
        if (array_key_exists('name', $data)) {
            $category->setName($data['name']);
        }

        if (isset($data['parent']) && $data['parent'] !== '') {
            if ($data['parent'] && ($data['parent'] != $category->getParent())) {
                if (!$this->dm->getRepository(Category::class)->find($data['parent'])) {
                    throw new CategoryException(
                        sprintf('Parent node [%s] not found', $data['parent']),
                        CategoryException::CATEGORY_NOT_FOUND,
                    );
                }
            }
            $category->setParent($data['parent']);
        }

        return $category;
    }

}
