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
     * @throws MetricsException
     */
    public function getTopologyMetrics(string $topologyId, array $params): array
    {
        /** @var Topology $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);
        if (!$topology) {
            throw new MetricsException(
                sprintf('Topology "%s" not found!', $topologyId),
                MetricsException::NODE_NOT_FOUND
            );
        }

        return $this->metricsManager->getTopologyMetrics($topology, $params);
    }

    /**
     * @param string $topologyId
     * @param string $nodeId
     * @param array  $params
     *
     * @return array
     * @throws MetricsException
     */
    public function getNodeMetrics(string $topologyId, string $nodeId, array $params): array
    {
        /** @var Node $node */
        $node = $this->dm->getRepository(Node::class)->findBy(['id' => $nodeId, 'topology' => $topologyId]);
        /** @var Topology $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($topologyId);

        if (!$node || !$topology) {
            throw new MetricsException(
                sprintf('Node "%s" with Topology "%s" not found!', $nodeId, $topologyId),
                MetricsException::NODE_NOT_FOUND
            );
        }

        return $this->metricsManager->getNodeMetrics($node, $topology, $params);
    }

}