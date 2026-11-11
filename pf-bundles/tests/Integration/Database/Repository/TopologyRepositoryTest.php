<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Database\Repository;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\CommonsBundle\Enum\TopologyStatusEnum;
use Hanaboso\PipesFramework\Database\Document\Category;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\Utils\Exception\EnumException;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class TopologyRepositoryTest
 *
 * @package PipesFrameworkTests\Integration\Database\Repository
 */
#[CoversClass(TopologyRepository::class)]
final class TopologyRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetTotalCount(): void
    {
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getTotalCount();
        self::assertSame(0, $result);

        $topology = new Topology();
        $topology->setName('name');

        $this->dm->persist($topology);
        $this->dm->flush();

        $result = $repo->getTotalCount();
        self::assertSame(1, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetCountByEnable(): void
    {
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getCountByEnable(TRUE);
        self::assertSame(0, $result);

        $topology = new Topology();
        $topology->setName('name')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(TRUE);

        $this->dm->persist($topology);
        $this->dm->flush();

        $result = $repo->getCountByEnable(TRUE);
        self::assertSame(1, $result);

        $result = $repo->getCountByEnable(FALSE);
        self::assertSame(0, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetPublishedCount(): void
    {
        $repo = $this->dm->getRepository(Topology::class);
        self::assertSame(0, $repo->getPublishedCount());

        $enabled = new Topology();
        $enabled
            ->setName('enabled')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(TRUE);

        $disabled = new Topology();
        $disabled
            ->setName('disabled')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(FALSE);

        $oldVersion = new Topology();
        $oldVersion
            ->setName('enabled')
            ->setVersion(2)
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(FALSE);

        $draft = new Topology();
        $draft
            ->setName('draft')
            ->setVisibility(TopologyStatusEnum::DRAFT->value)
            ->setEnabled(FALSE);

        $deleted = new Topology();
        $deleted
            ->setName('deleted')
            ->setVisibility(TopologyStatusEnum::PUBLIC->value)
            ->setEnabled(TRUE)
            ->setDeleted(TRUE);

        $this->dm->persist($enabled);
        $this->dm->persist($disabled);
        $this->dm->persist($oldVersion);
        $this->dm->persist($draft);
        $this->dm->persist($deleted);
        $this->dm->flush();

        // Slot semantics: every published row consumes a slot regardless
        // of `enabled` flag. Older versions count too. Drafts and deleted
        // rows are excluded.
        self::assertSame(3, $repo->getPublishedCount());
    }

    /**
     * @throws Exception
     */
    public function testGetMaxVersion(): void
    {
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getMaxVersion('name');
        self::assertSame(0, $result);

        $topology = new Topology();
        $topology
            ->setName('name')
            ->setVersion(5);
        $this->pfd($topology);

        $result = $repo->getMaxVersion('name');
        self::assertSame(5, $result);
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
        self::assertSame($topologyWithCategory->getId(), $topologies[0]->getId());
    }

    /**
     * @throws Exception
     */
    public function testGetTopologiesCountByName(): void
    {
        $repo   = $this->dm->getRepository(Topology::class);
        $result = $repo->getTopologiesCountByName('name');
        self::assertSame(0, $result);

        $topology = new Topology();
        $topology
            ->setName('name')
            ->setVersion(5);
        $this->pfd($topology);

        $result = $repo->getTopologiesCountByName('name');
        self::assertSame(1, $result);
    }

    /**
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
        self::assertSame('name', $result[0]->getName());
    }

    /**
     * @return void
     * @throws EnumException
     * @throws Exception
     * @throws MongoDBException
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
