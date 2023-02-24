<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Repository;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Database\Document\Category;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\Utils\Exception\EnumException;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyRepositoryTest
 *
 * @package PipesFrameworkTests\Integration\Database\Repository
 */
final class TopologyRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\TopologyRepository::getTotalCount
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
     * @covers \Hanaboso\PipesFramework\Database\Repository\TopologyRepository::getCountByEnable
     *
     * @throws Exception
     */
    public function testGetCountByEnable(): void
    {
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getCountByEnable(TRUE);
        self::assertEquals(0, $result);

        $topology = new Topology();
        $topology->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(TRUE);

        $this->dm->persist($topology);
        $this->dm->flush();

        $result = $repo->getCountByEnable(TRUE);
        self::assertEquals(1, $result);

        $result = $repo->getCountByEnable(FALSE);
        self::assertEquals(0, $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\TopologyRepository::getMaxVersion
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
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getRunnableTopologies('name');

        self::assertCount(0, $result);

        for ($i = 0; $i < 2; $i++) {
            $topology = new Topology();
            $topology
                ->setName('name')
                ->setEnabled(TRUE)
                ->setVisibility(TopologyStatusEnum::PUBLIC->value);
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
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getTopologies();
        self::assertCount(0, $result);

        for ($i = 0; $i < 2; $i++) {
            $topology = new Topology();
            $topology
                ->setName(sprintf('name-%s', $i))
                ->setEnabled(TRUE)
                ->setVisibility(TopologyStatusEnum::PUBLIC->value);
            $this->dm->persist($topology);
        }

        $this->dm->flush();
        $result = $repo->getTopologies();
        self::assertCount(2, $result);
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\TopologyRepository::getTopologiesByCategory
     *
     * @throws Exception
     */
    public function testGetTopologiesByCategory(): void
    {
        $repo     = $this->dm->getRepository(Topology::class);
        $category = new Category();
        $category->setName('test_category');
        $this->dm->persist($category);
        $this->dm->flush();

        $topologyNoCategory = new Topology();
        $topologyNoCategory
            ->setName('topology-no-category')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);

        $topologyWithCategory = new Topology();
        $topologyWithCategory
            ->setName('topology-with-category')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setCategory($category->getId());

        $this->dm->persist($topologyNoCategory);
        $this->dm->persist($topologyWithCategory);
        $this->dm->flush();

        $topologies = $repo->getTopologiesByCategory($category);

        self::assertCount(1, $topologies);
        self::assertEquals($topologyWithCategory->getId(), $topologies[0]->getId());
    }

    /**
     * @covers \Hanaboso\PipesFramework\Database\Repository\TopologyRepository::getTopologiesCountByName
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
     * @covers \Hanaboso\PipesFramework\Database\Repository\TopologyRepository::getPublicEnabledTopologies
     *
     * @throws Exception
     */
    public function testGetPublicEnabledTopologies(): void
    {
        $topology = new Topology();
        $topology
            ->setName('name')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);
        $this->pfd($topology);

        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getPublicEnabledTopologies();
        self::assertEquals('name', $result[0]->getName());
    }

    /**
     * @return void
     * @throws MongoDBException
     * @throws EnumException
     * @throws Exception
     */
    public function testGetTopologiesWithSameName(): void
    {
        $topology1 = new Topology();
        $topology1
            ->setName('testSame')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);

        $topology2 = new Topology();
        $topology2
            ->setName('testSame')
            ->setEnabled(TRUE)
            ->setVersion(2)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);

        $this->dm->persist($topology1);
        $this->dm->persist($topology2);
        $this->dm->flush();

        $repo       = $this->dm->getRepository(Topology::class);
        $topologies = $repo->getActiveTopologiesVersions($topology1->getId());

        $topologies = array_map(
            static fn($value): array => [
                'id'      => $value['_id'],
                'name'    => $value['name'],
                'version' => $value['version'],
            ],
            $topologies,
        );

        self::assertEquals(count($topologies), 2);
        self::assertEquals($topologies[0]['name'], 'testSame');
        self::assertEquals($topologies[1]['name'], 'testSame');
        self::assertEquals($topologies[0]['version'], 1);
        self::assertEquals($topologies[1]['version'], 2);
    }

    /**
     * @return void
     * @throws EnumException
     * @throws Exception
     */
    public function testGetTopologiesById(): void
    {
        $topology = new Topology();
        $topology
            ->setName('testTopo')
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);

        $this->pfd($topology);

        $topology1 = new Topology();
        $topology1
            ->setName('testTopo')
            ->setVersion(2)
            ->setEnabled(TRUE)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value);

        $this->pfd($topology1);

        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getActiveTopologiesVersions($topology->getId());

        self::assertEquals($result[0]['_id'], $topology->getId());
        self::assertEquals($result[0]['name'], $topology->getName());
        self::assertEquals($result[0]['version'], $topology->getVersion());

        self::assertEquals($result[1]['_id'], $topology1->getId());
        self::assertEquals($result[1]['name'], $topology1->getName());
        self::assertEquals($result[1]['version'], $topology1->getVersion());
    }

}
