<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Reduction\NodeReduction;

/**
 * Class NodeRepository
 *
 * @package Hanaboso\PipesFramework\Configurator\Repository
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
            ->field('topology')->equals($topologyId)
            ->field('type')->notIn(NodeReduction::$typeExclude)
            ->getQuery()
            ->toArray();
    }

    /**
     * @param string $nodeName
     * @param string $topologyId
     *
     * @return Node|null
     */
    public function getNodeByTopology(string $nodeName, string $topologyId): ?Node
    {
        /** @var Node $result */
        $result = $this->createQueryBuilder()
            ->field('name')->equals($nodeName)
            ->field('topology')->equals($topologyId)
            ->field('enabled')->equals(TRUE)
            ->getQuery()->getSingleResult();

        return $result;
    }

}