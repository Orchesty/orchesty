<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class LongRunningNodeDataRepository
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Repository
 */
class LongRunningNodeDataRepository extends DocumentRepository
{

    /**
     * @param string $topo
     *
     * @return array
     */
    public function getGroupStats(string $topo): array
    {
        $arr = $this->createQueryBuilder()->hydrate(FALSE)
            ->field('topologyId')->equals($topo)
            ->group(['nodeId' => 1], ['value' => 0])
            ->reduce('function (curr,result) {
                result.value++;
            }')
            ->getQuery()
            ->execute()->toArray();

        $res = [];
        foreach ($arr as $row) {
            $res[$row['nodeId']] = $row['value'];
        }

        return $res;
    }

}