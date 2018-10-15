<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Repository;

use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Repository\TopologyRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class TopologyRepositoryTest
 *
 * @package Tests\Integration\AppBundle\Repository
 */
final class TopologyRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testGetTopologyCount(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $top = new Topology();
            $top->setName('namae');
            $this->dm->persist($top);
        }
        $this->dm->flush();

        /** @var TopologyRepository $repo */
        $repo = $this->dm->getRepository(Topology::class);

        self::assertEquals(2, $repo->getTopologiesCountByName('namae'));
        self::assertEquals(0, $repo->getTopologiesCountByName('fdgh'));
    }

}