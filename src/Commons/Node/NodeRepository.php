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
     * @param string $nodeId
     */
    public function getNodeByTopology(string $topologyId, string $nodeId): void
    {
        count([$topologyId, $nodeId]);
    }

}