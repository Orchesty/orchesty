<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/28/17
 * Time: 1:55 PM
 */

namespace Tests\Integration\Category\Repository;

use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Category\Repository\CategoryRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class CategoryRepositoryTest
 *
 * @package Tests\Integration\Category\Repository
 */
class CategoryRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers CategoryRepository::childrenLevelUp()
     */
    public function testChildrenLevelUp(): void
    {
        /** @var CategoryRepository $repo */
        $repo = $this->dm->getRepository(Category::class);

        $rootCategory = new Category();
        $rootCategory->setName('root_cat');
        $this->dm->persist($rootCategory);
        $this->dm->flush();

        $category = new Category();
        $category->setName('cet_for_delete');
        $category->setParent($rootCategory->getId());
        $this->dm->persist($category);
        $this->dm->flush();

        $children = [];
        for ($i = 0; $i < 3; $i++) {
            $child = new Category();
            $child->setName('child_' . $i);
            $child->setParent($category->getId());
            $children[$i] = $child;
            $this->dm->persist($child);
        }
        $this->dm->flush();

        $repo->childrenLevelUp($category);
        $this->dm->clear();

        foreach ($children as $child) {
            $childCategory = $repo->find($child->getId());
            $this->assertEquals($rootCategory->getId(), $childCategory->getParent());
        }
    }

}