<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/27/17
 * Time: 12:10 PM
 */

namespace Hanaboso\PipesFramework\Category\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Category\Document\Category;

/**
 * Class CategoryRepository
 *
 * @package Hanaboso\PipesFramework\Category\Repository
 */
class CategoryRepository extends DocumentRepository
{

    /**
     * @param Category $category
     *
     * @throws MongoDBException
     */
    public function childrenLevelUp(Category $category): void
    {
        $this->createQueryBuilder()
            ->updateMany()
            ->field('parent')->equals($category->getId())
            ->field('parent')->set($category->getParent())
            ->getQuery()
            ->execute();
    }

}