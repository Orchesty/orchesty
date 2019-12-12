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
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Repository\LongRunningNodeDataRepository::getGroupStats()
     *
     * @throws Exception
     */
    public function testGroupStats(): void
    {
        $name = sprintf('topo-%s', uniqid());
        $this->prepData($name);
        /** @var LongRunningNodeDataRepository $repo */
        $repo = $this->dm->getRepository(LongRunningNodeData::class);
        $res  = $repo->getGroupStats($name);
        self::assertEquals(
            [
                'node0' => 2,
                'node1' => 1,
            ],
            $res
        );
    }

    /**
     * @param string $name
     *
     * @throws Exception
     */
    private function prepData(string $name): void
    {
        for ($i = 0; $i < 4; $i++) {
            $tmp = new LongRunningNodeData();
            $tmp
                ->setTopologyName($i < 3 ? $name : 'asd')
                ->setNodeName(sprintf('node%s', ($i % 2)));
            $this->dm->persist($tmp);
        }

        $this->dm->flush();
        $this->dm->clear();
    }

}
