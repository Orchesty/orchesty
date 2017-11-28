<?php declare(strict_types=1);

namespace Tests\Integration\Configurator\Repository;

use Hanaboso\PipesFramework\Category\Document\Category;
use Hanaboso\PipesFramework\Commons\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyRepositoryTest
 *
 * @package Tests\Integration\Configurator\Repository
 */
final class TopologyRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers TopologyRepository::getTotalCount()
     */
    public function testGetTotalCount(): void
    {
        /** @var TopologyRepository $repo */
        $repo = $this->dm->getRepository(Topology::class);

        $result = $repo->getTotalCount();

        self::assertEquals(0, $result);

        $topology = new Topology();
        $topology->setName('name');

        $this->dm->persist($topology);
        $this->dm->flush();

        $result = $repo->getTotalCount();

        self::assertEquals(1, $result);
    }

    /**
     *
     */
    public function testGetRunnableTopologies(): void
    {
        /** @var TopologyRepository $repo */
        $repo = $this->dm->getRepository(Topology::class);

        $result = $repo->getRunnableTopologies('name');

        self::assertCount(0, $result);

        for ($i = 0; $i < 2; $i++) {
            $topology = new Topology();
            $topology
                ->setName('name')
                ->setEnabled(TRUE)
                ->setVisibility(TopologyStatusEnum::PUBLIC);
            $this->dm->persist($topology);
        }

        $this->dm->flush();

        $result = $repo->getRunnableTopologies('name');

        self::assertCount(2, $result);

    }

    /**
     *
     */
    public function testGetTopologies(): void
    {
        /** @var TopologyRepository $repo */
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getTopologies();

        self::assertCount(0, $result);

        for ($i = 0; $i < 2; $i++) {
            $topology = new Topology();
            $topology
                ->setName(sprintf('name-%s', $i))
                ->setEnabled(TRUE)
                ->setVisibility(TopologyStatusEnum::PUBLIC);
            $this->dm->persist($topology);
        }

        $this->dm->flush();

        $result = $repo->getTopologies();

        self::assertCount(2, $result);

    }

    /**
     * @covers TopologyRepository::getTopologiesByCategory()
     */
    public function testGetTopologiesByCategory(): void
    {
        /** @var TopologyRepository $repo */
        $repo   = $this->dm->getRepository(Topology::class);
        $category = new Category();
        $category->setName('test_category');
        $this->dm->persist($category);
        $this->dm->flush();

        $topologyNoCategory = new Topology();
        $topologyNoCategory
            ->setName('topology-no-category')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC);

        $topologyWithCategory = new Topology();
        $topologyWithCategory
            ->setName('topology-with-category')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC)
            ->setCategory($category->getId());

        $this->dm->persist($topologyNoCategory);
        $this->dm->persist($topologyWithCategory);
        $this->dm->flush();

        $topologies = $repo->getTopologiesByCategory($category);

        self::assertCount(1, $topologies);
        self::assertEquals($topologyWithCategory->getId(), $topologies[0]->getId());
    }

}