<?php declare(strict_types=1);

namespace Tests\Integration\Configurator\Repository;

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

}