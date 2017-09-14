<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
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
            ->field('topology')
            ->equals($topologyId)
            ->field('type')
            ->notIn(NodeReduction::$typeExclude)
            ->getQuery()
            ->toArray();
    }

}