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
     * @return integer
     */
    public function getTotalCount(): int
    {
        return $this->createQueryBuilder()->count()->getQuery()->execute();
    }

    /**
     * @param string $topologyId
     * @param string $nodeId
     */
    public function getNodeByTopology(string $topologyId, string $nodeId): void
    {
        count([$topologyId, $nodeId]);
    }

}