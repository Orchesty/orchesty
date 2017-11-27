<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/27/17
 * Time: 3:23 PM
 */

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Category\Model\CategoryManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocatorInterface;

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
        $this->dm              = $dml->getDm();
        $this->categoryManager = $categoryManager;
    }

    public function getCategories()
    {
        $categories = $this->dm->getRepository(Category::class)->findAll();

        $data = [
            'items' => [],
        ];
        foreach ($categories as $category) {
            $data['items'][] = $this->getCategoryData($category);
        }

        $data['total']  = count($categories);
        $data['limit']  = NULL;
        $data['count']  = count($categories);
        $data['offset'] = NULL;

        return $data;
    }

    /**
     * @param Category $category
     *
     * @return array
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