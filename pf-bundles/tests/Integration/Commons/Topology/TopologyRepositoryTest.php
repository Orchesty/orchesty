<?php declare(strict_types=1);

namespace Tests\Integration\Commons\Topology;

use Hanaboso\PipesFramework\Commons\Topology\Document\Topology;
use Hanaboso\PipesFramework\Commons\Topology\TopologyRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyRepositoryTest
 *
 * @package Tests\Integration\Commons\Topology
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

}