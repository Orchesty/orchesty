<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Database\Repository;

use Exception;
use Hanaboso\PipesPhpSdk\Database\Document\Category;
use Hanaboso\PipesPhpSdk\Database\Repository\CategoryRepository;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class CategoryRepositoryTest
 *
 * @package PipesPhpSdkTests\Integration\Database\Repository
 */
final class CategoryRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Repository\CategoryRepository::childrenLevelUp()
     *
     * @throws Exception
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
            $child->setName(sprintf('child%s', $i));
            $child->setParent($category->getId());
            $children[$i] = $child;
            $this->dm->persist($child);
        }
        $this->dm->flush();

        $repo->childrenLevelUp($category);
        $this->dm->clear();

        foreach ($children as $child) {
            /** @var Category $childCategory */
            $childCategory = $repo->find($child->getId());
            self::assertEquals($rootCategory->getId(), $childCategory->getParent());
        }
    }

}
