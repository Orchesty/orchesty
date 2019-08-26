<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\MongoDBException;

/**
 * Class LongRunningNodeDataRepository
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Repository
 */
class LongRunningNodeDataRepository extends DocumentRepository
{

    /**
     * @param string $topo
     *
     * @return array
     * @throws MongoDBException
     */
    public function getGroupStats(string $topo): array
    {
        // TODO: not working with latest Mongo!
        $arr = $this->createQueryBuilder()->hydrate(FALSE)
            ->field('topologyName')->equals($topo)
            ->group(['nodeName' => 1], ['value' => 0])
            ->reduce('function (curr,result) {
                result.value++;
            }')
            ->getQuery()
            ->execute()->toArray();

        $res = [];
        foreach ($arr as $row) {
            $res[$row['nodeName']] = $row['value'];
        }

        return $res;
    }

}
