<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\CommonsBundle\Database\Document\Category;
use Hanaboso\CommonsBundle\Database\Document\Topology;
use Hanaboso\CommonsBundle\Database\Locator\DatabaseManagerLocator;
use Hanaboso\CommonsBundle\Database\Repository\CategoryRepository;
use Hanaboso\CommonsBundle\Database\Repository\TopologyRepository;
use Hanaboso\CommonsBundle\Exception\CategoryException;

/**
 * Class CategoryManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
class CategoryManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

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
     * @param array $data
     *
     * @return Category
     * @throws CategoryException
     * @throws LockException
     * @throws MappingException
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
     * @param array    $data
     *
     * @return Category
     * @throws CategoryException
     * @throws LockException
     * @throws MappingException
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
        /** @var TopologyRepository $topologyRepository */
        $topologyRepository = $this->dm->getRepository(Topology::class);
        $topologies         = $topologyRepository->getTopologiesByCategory($category);
        if (count($topologies) == 0) {
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = $this->dm->getRepository(Category::class);
            $categoryRepository->childrenLevelUp($category);
            $this->dm->remove($category);
            $this->dm->flush();
        } else {
            throw new CategoryException(
                'Category used by topology cannot be remove.',
                CategoryException::CATEGORY_USED
            );
        }
    }

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     * @throws CategoryException
     * @throws LockException
     * @throws MappingException
     */
    private function setCategoryData(Category $category, array $data): Category
    {
        if (array_key_exists('name', $data)) {
            $category->setName($data['name']);
        }

        if (array_key_exists('parent', $data)) {
            if ($data['parent'] && ($data['parent'] != $category->getParent())) {
                if (!$this->dm->getRepository(Category::class)->find($data['parent'])) {
                    throw new CategoryException(
                        sprintf('Parent node [%s] not found', $data['parent']),
                        CategoryException::CATEGORY_NOT_FOUND
                    );
                }
            }
            $category->setParent($data['parent']);
        }

        return $category;
    }

}
