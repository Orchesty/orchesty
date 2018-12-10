<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/28/17
 * Time: 3:20 PM
 */

namespace Tests\Integration\Category\Model;

use Exception;
use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Category\Exception\CategoryException;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class CategoryManagerTest
 *
 * @package Tests\Integration\Category\Model
 */
final class CategoryManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testManager(): void
    {
        $manager         = $this->ownContainer->get('hbpf.configurator.manager.category');
        $repo            = $this->dm->getRepository(Category::class);
        $topologyManager = $this->ownContainer->get('hbpf.configurator.manager.topology');

        $dataR1 = [
            'name'   => 'root1',
            'parent' => NULL,
        ];

        $dataR2 = [
            'name'   => 'root2',
            'parent' => NULL,
        ];

        $categoryR1 = $manager->createCategory($dataR1);
        $categoryR2 = $manager->createCategory($dataR2);

        $dataCh1 = [
            'name'   => 'child1',
            'parent' => $categoryR1->getId(),
        ];

        $dataCh2 = [
            'name'   => 'child2',
            'parent' => $categoryR2->getId(),
        ];

        $manager->createCategory($dataCh1);
        $categoryCh2 = $manager->createCategory($dataCh2);

        $dataCh2Edit = [
            'name'   => 'child2_edited',
            'parent' => $categoryR1->getId(),
        ];

        $manager->updateCategory($categoryCh2, $dataCh2Edit);

        self::assertCount(4, $repo->findAll());
        self::assertCount(2, $repo->findBy(['parent' => NULL]));
        self::assertCount(2, $repo->findBy(['parent' => $categoryR1->getId()]));
        self::assertCount(0, $repo->findBy(['parent' => $categoryR2->getId()]));

        $manager->deleteCategory($categoryR1);

        self::assertCount(3, $repo->findAll());
        self::assertCount(3, $repo->findBy(['parent' => NULL]));
        self::assertCount(0, $repo->findBy(['parent' => $categoryR2->getId()]));

        $topologyManager->createTopology(['name' => 'Topology', 'category' => $categoryCh2->getId()]);

        $this->expectException(CategoryException::class);
        $this->expectExceptionCode(CategoryException::CATEGORY_USED);
        $manager->deleteCategory($categoryCh2);
    }

}