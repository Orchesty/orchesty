<?php declare(strict_types=1);

namespace Tests\Integration\LongRunningNode\Repository;

use Exception;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Repository\LongRunningNodeDataRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class LongRunningNodeDataRepositoryTest
 *
 * @package Tests\Integration\LongRunningNode\Repository
 */
final class LongRunningNodeDataRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers LongRunningNodeDataRepository::getGroupStats()
     *
     * @throws Exception
     */
    public function testGroupStats(): void
    {
        $this->prepData();
        /** @var LongRunningNodeDataRepository $repo */
        $repo = $this->dm->getRepository(LongRunningNodeData::class);
        $res  = $repo->getGroupStats('topo');
        self::assertEquals(
            [
                'node0' => 2,
                'node1' => 1,
            ],
            $res
        );
    }

    /**
     * @throws Exception
     */
    private function prepData(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $tmp = new LongRunningNodeData();
            $tmp->setTopologyName($i < 3 ? 'topo' : 'asd')
                ->setNodeName(sprintf('node%s', ($i % 2)));
            $this->dm->persist($tmp);
        }

        $this->dm->flush();
    }

}
