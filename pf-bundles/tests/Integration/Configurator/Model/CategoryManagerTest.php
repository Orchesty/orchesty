<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Configurator\Model;

use Exception;
use Hanaboso\CommonsBundle\Exception\CategoryException;
use Hanaboso\PipesFramework\Database\Document\Category;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class CategoryManagerTest
 *
 * @package PipesFrameworkTests\Integration\Configurator\Model
 */
final class CategoryManagerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::createCategory
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::setCategoryData
     * @covers \Hanaboso\PipesFramework\Configurator\Model\CategoryManager::deleteCategory
     *
     * @throws Exception
     */
    public function testManager(): void
    {
        $manager         = self::getContainer()->get('hbpf.configurator.manager.category');
        $repo            = $this->dm->getRepository(Category::class);
        $topologyManager = self::getContainer()->get('hbpf.configurator.manager.topology');

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

        self::expectException(CategoryException::class);
        self::expectExceptionCode(CategoryException::CATEGORY_USED);
        $manager->deleteCategory($categoryCh2);
    }

}
