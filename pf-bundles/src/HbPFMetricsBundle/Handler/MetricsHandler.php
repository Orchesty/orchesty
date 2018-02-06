<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 11:31
 */

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\MetricsManager;

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
     * @var MetricsManager
     */
    private $metricsManager;

    /**
     * MetricsHandler constructor.
     *
     * @param DocumentManager $dm
     * @param MetricsManager  $metricsManager
     */
    public function __construct(DocumentManager $dm, MetricsManager $metricsManager)
    {
        $this->dm             = $dm;
        $this->metricsManager = $metricsManager;
    }

    /**
     * @param string $topologyId
     * @param array  $params
     *
     * @return array
     */
    public function getTopologyMetrics(string $topologyId, array $params): array
    {
        return $this->metricsManager->getTopologyMetrics($this->getTopologyById($topologyId), $params);
    }

    /**
     * @param string $topologyId
     * @param string $nodeId
     * @param array  $params
     *
     * @return array
     */
    public function getNodeMetrics(string $topologyId, string $nodeId, array $params): array
    {
        return $this->metricsManager->getNodeMetrics(
            $this->getNodeByTopologyAndNodeId($topologyId, $nodeId),
            $this->getTopologyById($topologyId),
            $params
        );
    }

    /**
     * @param string $topologyId
     * @param array  $params
     *
     * @return array
     */
    public function getRequestsCountMetrics(string $topologyId, array $params): array
    {
        return $this->metricsManager->getTopologyRequestCountMetrics($this->getTopologyById($topologyId), $params);
    }

    /**
     * @param string $id
     *
     * @return Topology
     * @throws MetricsException
     */
    private function getTopologyById(string $id): Topology
    {
        /** @var Topology $topology */
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
        /** @var Node $node */
        $node = $this->dm->getRepository(Node::class)->findBy(['id' => $nodeId, 'topology' => $topologyId]);

        if (!$node) {
            throw new MetricsException(
                sprintf('Node "%s" with topology "%s" not found!', $nodeId, $topologyId),
                MetricsException::NODE_NOT_FOUND
            );
        }

        return $node;
    }

}