<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Database\Repository;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use Hanaboso\CommonsBundle\Enum\HandlerEnum;
use Hanaboso\CommonsBundle\Enum\TypeEnum;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\PipesPhpSdk\Database\Reduction\NodeReduction;
use LogicException;

/**
 * Class NodeRepository
 *
 * @package         Hanaboso\PipesPhpSdk\Database\Repository
 *
 * @phpstan-extends DocumentRepository<Node>
 */
final class NodeRepository extends DocumentRepository
{

    /**
     * @param string $topologyId
     *
     * @return Node[]
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
        /** @var Node|null $node */
        $node = $this->createQueryBuilder()
            ->field('topology')->equals($topology->getId())
            ->field('type')->equals(TypeEnum::SIGNAL)
            ->field('handler')->equals(HandlerEnum::EVENT)
            ->field('enabled')->equals(TRUE)
            ->getQuery()->getSingleResult();

        if (!$node) {
            throw new LogicException(
                sprintf('Starting Node not found for topology [%s]', $topology->getId()),
            );
        }

        return $node;
    }

    /**
     * @param Topology $topology
     *
     * @return string
     * @throws MongoDBException
     */
    public function getTopologyType(Topology $topology): string
    {
        $hasCron = count($this->getCronNodes($topology));

        return $hasCron === 1 ? TypeEnum::CRON : TypeEnum::WEBHOOK;
    }

    /**
     * @param Topology $topology
     *
     * @return Node[]
     * @throws MongoDBException
     */
    public function getCronNodes(Topology $topology): array
    {
        return $this->createQueryBuilder()
            ->field('topology')->equals($topology->getId())
            ->field('type')->equals(TypeEnum::CRON)
            ->getQuery()->toArray();
    }

    /**
     * @param string $topologyId
     *
     * @return Node[]
     */
    public function getNodesByTopology(string $topologyId): array
    {
        return $this->createQueryBuilder()
            ->field('topology')->equals($topologyId)
            ->getQuery()
            ->toArray();
    }

    /**
     * @param string $nodeId
     *
     * @return Node
     */
    public function getNodeById(string $nodeId): Node {
        /** @var Node|null $node */
        $node = $this->createQueryBuilder()
            ->field('id')->equals($nodeId)
            ->field('deleted')->equals(FALSE)
            ->field('enabled')->equals(TRUE)
            ->getQuery()->getSingleResult();

        if (!$node) {
            throw new LogicException(
                sprintf('Node with id is not found [%s]', $nodeId),
            );
        }

        return $node;
    }

}
