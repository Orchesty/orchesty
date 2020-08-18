<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Repository;

use Doctrine\ODM\MongoDB\Iterator\Iterator;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;
use Hanaboso\PipesPhpSdk\LongRunningNode\Enum\StateEnum;

/**
 * Class LongRunningNodeDataRepository
 *
 * @package         Hanaboso\PipesPhpSdk\LongRunningNode\Repository
 *
 * @phpstan-extends DocumentRepository<LongRunningNodeData>
 */
final class LongRunningNodeDataRepository extends DocumentRepository
{

    /**
     * @param string $processId
     *
     * @return LongRunningNodeData|null
     * @throws MongoDBException
     */
    public function getProcessed(string $processId): ?LongRunningNodeData
    {
        /** @var Iterator<LongRunningNodeData> $iterator */
        $iterator = $this->createQueryBuilder()
            ->field(LongRunningNodeData::PROCESS_ID)->equals($processId)
            ->field(LongRunningNodeData::STATE)->in([StateEnum::ACCEPTED, StateEnum::CANCELED])
            ->getQuery()
            ->execute();

        $data = $iterator->toArray();

        return $data ? array_values($data)[0] : NULL;
    }

    /**
     * @param string $topo
     *
     * @return mixed[]
     */
    public function getGroupStats(string $topo): array
    {
        $arr = $this->createAggregationBuilder()
            ->match()->field('topologyName')->equals($topo)
            ->group()->field('id')->expression('$nodeName')
            ->field('nodeName')->first('$nodeName')
            ->field('value')->sum(1)
            ->execute()->toArray();

        $res = [];
        foreach ($arr as $row) {
            $res[$row['nodeName']] = $row['value'];
        }

        return $res;
    }

}
