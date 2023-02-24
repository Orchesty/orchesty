<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class MetricsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Handler
 */
final class MetricsHandler
{

    use GridHandlerTrait;

    /**
     * MetricsHandler constructor.
     *
     * @param DocumentManager     $dm
     * @param MongoMetricsManager $mongoMetricsManager
     */
    public function __construct(private DocumentManager $dm, private MongoMetricsManager $mongoMetricsManager)
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
        return $this->mongoMetricsManager->getTopologyMetrics($this->getTopologyById($topologyId), $params);
    }

    /**
     * @param string  $topologyId
     * @param string  $nodeId
     * @param mixed[] $params
     *
     * @return mixed[]
     * @throws DateTimeException
     * @throws MetricsException
     */
    public function getNodeMetrics(string $topologyId, string $nodeId, array $params): array
    {
        return $this->mongoMetricsManager->getNodeMetrics(
            $this->getNodeByTopologyAndNodeId($topologyId, $nodeId),
            $this->getTopologyById($topologyId),
            $params,
        );
    }

    /**
     * @return mixed[]
     * @throws DateTimeException
     */
    public function getHealthcheckMetrics(): array
    {
        return $this->mongoMetricsManager->getHealthcheckMetrics();
    }

    /**
     * @param string                  $topologyId
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws DateTimeException
     * @throws MetricsException
     */
    public function getRequestsCountMetrics(string $topologyId, GridRequestDtoInterface $dto): array
    {
        $params = $this->parseDateRangeFromFilter($dto);
        $items  = $this->mongoMetricsManager->getTopologyRequestCountMetrics(
            $this->getTopologyById($topologyId),
            $params,
        );

        return $this->getGridResponse($dto, $items);
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

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     */
    private function parseDateRangeFromFilter(GridRequestDtoInterface $dto): array
    {
        $params = []; // from / to
        foreach ($dto->getFilter() as $and) {
            foreach ($and as $or) {
                $column = $or[GridFilterAbstract::COLUMN] ?? '';
                if ($column == 'timestamp') {
                    $params = [
                        'from' => $or[GridFilterAbstract::VALUE][0] ?? NULL,
                        'to'   => $or[GridFilterAbstract::VALUE][1] ?? NULL,
                    ];

                    break;
                }
            }
        }

        return $params;
    }

}
