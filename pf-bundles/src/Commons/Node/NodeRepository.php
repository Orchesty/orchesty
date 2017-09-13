<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Node;

use Doctrine\ODM\MongoDB\DocumentRepository;

/**
 * Class NodeRepository
 *
 * @package Hanaboso\PipesFramework\Commons\Node
 */
class NodeRepository extends DocumentRepository
{

    /**
     * @param string $topologyId
     *
     * @return array
     */
    public function getEventNodesByTopology(string $topologyId): array
    {
        return $this->createQueryBuilder()
            ->field('topology')
            ->equals($topologyId)
            ->field('type')
            ->notIn(NodeReduction::$typeExclude)
            ->getQuery()
            ->toArray();
    }

}