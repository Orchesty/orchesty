<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;

/**
 * Class MetricsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Handler
 */
class MetricsHandler
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var MetricsManagerLoader
     */
    private $loader;

    /**
     * MetricsHandler constructor.
     *
     * @param DocumentManager      $dm
     * @param MetricsManagerLoader $loader
     */
    public function __construct(DocumentManager $dm, MetricsManagerLoader $loader)
    {
        $this->dm     = $dm;
        $this->loader = $loader;
    }

    /**
     * @param string  $topologyId
     * @param mixed[] $params
     *
     * @return mixed[]
     * @throws LockException
     * @throws MappingException
     * @throws MetricsException
     */
    public function getTopologyMetrics(string $topologyId, array $params): array
    {
        return $this->loader->getManager()->getTopologyMetrics($this->getTopologyById($topologyId), $params);
    }

    /**
     * @param string  $topologyId
     * @param string  $nodeId
     * @param mixed[] $params
     *
     * @return mixed[]
     * @throws LockException
     * @throws MappingException
     * @throws MetricsException
     */
    public function getNodeMetrics(string $topologyId, string $nodeId, array $params): array
    {
        return $this->loader->getManager()->getNodeMetrics(
            $this->getNodeByTopologyAndNodeId($topologyId, $nodeId),
            $this->getTopologyById($topologyId),
            $params
        );
    }

    /**
     * @param string  $topologyId
     * @param mixed[] $params
     *
     * @return mixed[]
     * @throws LockException
     * @throws MappingException
     * @throws MetricsException
     */
    public function getRequestsCountMetrics(string $topologyId, array $params): array
    {
        return $this->loader->getManager()->getTopologyRequestCountMetrics(
            $this->getTopologyById($topologyId),
            $params
        );
    }

    /**
     * @param string $id
     *
     * @return Topology
     * @throws LockException
     * @throws MappingException
     * @throws MetricsException
     */
    private function getTopologyById(string $id): Topology
    {
        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($id);

        if (!$topology) {
            throw new MetricsException(
                sprintf('Topology "%s" not found!', $id),
                MetricsException::TOPOLOGY_NOT_FOUND
            );
        }

        return $topology;
    }

    /**
     * @param string $topologyId
     * @param string $nodeId
     *
     * @return Node
     * @throws MetricsException
     */
    private function getNodeByTopologyAndNodeId(string $topologyId, string $nodeId): Node
    {
        /** @var Node|null $node */
        $node = $this->dm->getRepository(Node::class)->findOneBy(['id' => $nodeId, 'topology' => $topologyId]);

        if (!$node) {
            throw new MetricsException(
                sprintf('Node "%s" with topology "%s" not found!', $nodeId, $topologyId),
                MetricsException::NODE_NOT_FOUND
            );
        }

        return $node;
    }

}
