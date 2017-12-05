<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/27/17
 * Time: 12:11 PM
 */

namespace Hanaboso\PipesFramework\Category\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Category\Exception\CategoryException;
use Hanaboso\PipesFramework\Category\Repository\CategoryRepository;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;

/**
 * Class CategoryManager
 *
 * @package Hanaboso\PipesFramework\Category\Model
 */
class CategoryManager
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * TopologyManager constructor.
     *
     * @param DatabaseManagerLocator $dml
     */
    function __construct(DatabaseManagerLocator $dml)
    {
        $this->dm = $dml->getDm();
    }

    /**
     * @param array $data
     *
     * @return Category
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
     */
    public function deleteCategory(Category $category): void
    {
        /** @var TopologyRepository $topologyRepository */
        $topologyRepository = $this->dm->getRepository(Topology::class);
        $topologies = $topologyRepository->getTopologiesByCategory($category);
        if (count($topologies) == 0) {
            /** @var CategoryRepository $categoryRepository */
            $categoryRepository = $this->dm->getRepository(Category::class);
            $categoryRepository->childrenLevelUp($category);
            $this->dm->remove($category);
            $this->dm->flush();
        } else {
            throw new CategoryException('Category is used in topology.', CategoryException::CATEGORY_USED);
        }
    }

    /**
     * @param Category $category
     * @param array    $data
     *
     * @return Category
     * @throws CategoryException
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