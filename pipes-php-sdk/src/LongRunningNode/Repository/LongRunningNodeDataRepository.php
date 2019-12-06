<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Repository;

use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

/**
 * Class LongRunningNodeDataRepository
 *
 * @package         Hanaboso\PipesPhpSdk\LongRunningNode\Repository
 * @phpstan-extends DocumentRepository<\Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData>
 */
class LongRunningNodeDataRepository extends DocumentRepository
{

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
