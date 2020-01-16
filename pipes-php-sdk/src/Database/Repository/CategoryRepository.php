<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Repository;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesPhpSdk\Database\Document\Category;

/**
 * Class CategoryRepository
 *
 * @package         Hanaboso\PipesPhpSdk\Database\Repository
 *
 * @phpstan-extends DocumentRepository<Category>
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
