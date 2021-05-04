<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;

/**
 * Class MetricsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Handler
 */
final class MetricsHandler
{

    /**
     * MetricsHandler constructor.
     *
     * @param DocumentManager      $dm
     * @param MetricsManagerLoader $loader
     */
    public function __construct(private DocumentManager $dm, private MetricsManagerLoader $loader)
    {
    }

    /**
     * @param string  $topologyId
     * @param mixed[] $params
     *
     * @return mixed[]
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
     * @throws MetricsException
     */
    public function getNodeMetrics(string $topologyId, string $nodeId, array $params): array
    {
        return $this->loader->getManager()->getNodeMetrics(
            $this->getNodeByTopologyAndNodeId($topologyId, $nodeId),
            $this->getTopologyById($topologyId),
            $params,
        );
    }

    /**
     * @param string  $topologyId
     * @param mixed[] $params
     *
     * @return mixed[]
     * @throws MetricsException
     */
    public function getRequestsCountMetrics(string $topologyId, array $params): array
    {
        return $this->loader->getManager()->getTopologyRequestCountMetrics(
            $this->getTopologyById($topologyId),
            $params,
        );
    }

    /**
     * @param mixed[]     $params
     * @param string|null $key
     *
     * @return mixed[]
     */
    public function getApplicationMetrics(array $params, ?string $key): array
    {
        return $this->loader->getManager()->getApplicationMetrics($params, $key);
    }

    /**
     * @param mixed[]     $params
     * @param string|null $user
     *
     * @return mixed[]
     */
    public function getUserMetrics(array $params, ?string $user): array
    {
        return $this->loader->getManager()->getUserMetrics($params, $user);
    }

    /**
     * @param string $id
     *
     * @return Topology
     * @throws MetricsException
     */
    private function getTopologyById(string $id): Topology
    {
        /** @var Topology|null $topology */
        $topology = $this->dm->getRepository(Topology::class)->find($id);

        if (!$topology) {
            throw new MetricsException(
                sprintf('Topology "%s" not found!', $id),
                MetricsException::TOPOLOGY_NOT_FOUND,
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
                MetricsException::NODE_NOT_FOUND,
            );
        }

        return $node;
    }

}
