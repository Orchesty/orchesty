<?php declare(strict_types=1);

namespace Tests\Integration\Configurator\Repository;

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

}