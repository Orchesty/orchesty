<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFMetricsBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridHandlerTrait;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use Hanaboso\Utils\Exception\DateTimeException;

/**
 * Class MetricsHandler
 *
 * @package Hanaboso\PipesFramework\HbPFMetricsBundle\Handler
 */
final readonly class MetricsHandler
{

    use GridHandlerTrait;

    /**
     * MetricsHandler constructor.
     *
     * @param DocumentManager     $dm
     * @param MongoMetricsManager $manager
     */
    public function __construct(private DocumentManager $dm, private MongoMetricsManager $manager)
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
        return $this->manager->getTopologyMetrics($this->getTopologyById($topologyId), $params);
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
        return $this->manager->getNodeMetrics(
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
        return $this->manager->getHealthcheckMetrics();
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
        $items  = $this->manager->getTopologyRequestCountMetrics(
            $this->getTopologyById($topologyId),
            $params,
        );

        return $this->getGridResponse($dto, $items);
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMetricsConnectorsOverview(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getMetricsConnectorsOverview($dto));
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMetricsConnectors(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getMetricsConnectors($dto));
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMetricsConnectorsGraph(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getMetricsConnectorsGraph($dto));
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMetricsRequests(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getMetricsRequests($dto));
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMetricsProcesses(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getMetricsProcesses($dto));
    }


    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMetricsLimits(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getMetricsLimits($dto));
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMetricsLimitsTotal(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getMetricsLimitsTotal($dto));
    }


    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws Exception
     */
    public function getMetricsLimitsGraph(GridRequestDtoInterface $dto): array
    {
        return $this->getGridResponse($dto, $this->manager->getMetricsLimitsGraph($dto));
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
                        'size' => $or[GridFilterAbstract::VALUE][2] ?? NULL,
                        'to'   => $or[GridFilterAbstract::VALUE][1] ?? NULL,
                    ];

                    break;
                }
            }
        }

        return $params;
    }

}
