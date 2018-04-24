<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Repository;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Configurator\Reduction\NodeReduction;
use LogicException;

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

    /**
     * @param Topology $topology
     *
     * @return Node
     */
    public function getStartingNode(Topology $topology): Node
    {
        /** @var Node $node */
        $node = $this->createQueryBuilder()
            ->field('topology')->equals($topology->getId())
            ->field('type')->equals(TypeEnum::SIGNAL)
            ->field('handler')->equals(HandlerEnum::EVENT)
            ->field('enabled')->equals(TRUE)
            ->getQuery()->getSingleResult();

        if (!$node) {
            throw new LogicException(
                sprintf('Starting Node not found for topology [%s]', $topology->getId())
            );
        }

        return $node;
    }

}