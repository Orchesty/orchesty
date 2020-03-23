<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Database\Repository;

use Exception;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesPhpSdk\Database\Document\Category;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyRepositoryTest
 *
 * @package PipesPhpSdkTests\Integration\Database\Repository
 */
final class TopologyRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository::getTotalCount
     *
     * @throws Exception
     */
    public function testGetTotalCount(): void
    {
        $repo   = $this->dm->getRepository(Topology::class);
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
     * @covers \Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository::getMaxVersion
     *
     * @throws Exception
     */
    public function testGetMaxVersion(): void
    {
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getMaxVersion('name');
        self::assertEquals(0, $result);

        $topology = new Topology();
        $topology
            ->setName('name')
            ->setVersion(5);
        $this->pfd($topology);

        $result = $repo->getMaxVersion('name');
        self::assertEquals(5, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetRunnableTopologies(): void
    {
        /** @var TopologyRepository $repo */
        $repo   = $this->dm->getRepository(Topology::class);
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
     * @throws Exception
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
     * @covers \Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository::getTopologiesByCategory
     *
     * @throws Exception
     */
    public function testGetTopologiesByCategory(): void
    {
        /** @var TopologyRepository $repo */
        $repo     = $this->dm->getRepository(Topology::class);
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

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository::getTopologiesCountByName
     *
     * @throws Exception
     */
    public function testGetTopologiesCountByName(): void
    {
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getTopologiesCountByName('name');
        self::assertEquals(0, $result);

        $topology = new Topology();
        $topology
            ->setName('name')
            ->setVersion(5);
        $this->pfd($topology);

        $result = $repo->getTopologiesCountByName('name');
        self::assertEquals(1, $result);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Database\Repository\TopologyRepository::getPublicEnabledTopologies
     *
     * @throws Exception
     */
    public function testGetPublicEnabledTopologies(): void
    {
        $topology = new Topology();
        $topology
            ->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC);
        $this->pfd($topology);

        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getPublicEnabledTopologies();
        self::assertEquals('name', $result[0]->getName());
    }

}
