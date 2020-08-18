<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\LongRunningNode\Repository;

use Exception;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Enum\StateEnum;
use Hanaboso\PipesPhpSdk\LongRunningNode\Repository\LongRunningNodeDataRepository;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class LongRunningNodeDataRepositoryTest
 *
 * @package PipesPhpSdkTests\Integration\LongRunningNode\Repository
 */
final class LongRunningNodeDataRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     * @var LongRunningNodeDataRepository
     */
    private LongRunningNodeDataRepository $repository;

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Repository\LongRunningNodeDataRepository::getProcessed
     *
     * @throws Exception
     */
    public function testGetProcessed(): void
    {
        $this->dm->persist((new LongRunningNodeData())->setProcessId('processId')->setState(StateEnum::ACCEPTED));
        $this->dm->flush();

        self::assertNotNull($this->repository->getProcessed('processId'));
        self::assertNull($this->repository->getProcessed('anotherProcessId'));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\LongRunningNode\Repository\LongRunningNodeDataRepository::getGroupStats
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
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->dm->getRepository(LongRunningNodeData::class);
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
                ->setNodeName(sprintf('node%s', $i % 2));
            $this->dm->persist($tmp);
        }

        $this->dm->flush();
        $this->dm->clear();
    }

}
