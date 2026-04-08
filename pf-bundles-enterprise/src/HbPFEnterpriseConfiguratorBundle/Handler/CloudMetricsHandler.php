<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\PipesFramework\Configurator\Model\TopologyGenerator\TopologyGeneratorBridge;
use Hanaboso\PipesFramework\Configurator\Model\TopologyManager;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Database\Repository\TopologyRepository;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;
use Hanaboso\PipesFramework\Metrics\Document\RepeaterMetrics;
use MongoDB\BSON\UTCDateTime;
use Throwable;

/**
 * Class CloudMetricsHandler
 *
 * @package Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler
 */
final class CloudMetricsHandler
{

    /**
     * CloudMetricsHandler constructor.
     *
     * @param DocumentManager         $dm
     * @param DocumentManager         $metricsDm
     * @param TopologyGeneratorBridge $generatorBridge
     * @param TopologyManager         $topologyManager
     */
    public function __construct(
        private readonly DocumentManager $dm,
        private readonly DocumentManager $metricsDm,
        private readonly TopologyGeneratorBridge $generatorBridge,
        private readonly TopologyManager $topologyManager,
    )
    {
    }

    /**
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getPublishedTopologiesCount(): array
    {
        /** @var TopologyRepository<Topology> $repo */
        $repo = $this->dm->getRepository(Topology::class);

        $enabled  = $repo->getCountByEnable(TRUE);
        $disabled = $repo->getCountByEnable(FALSE);

        return [
            'disabled' => $disabled,
            'enabled'  => $enabled,
            'total'    => $enabled + $disabled,
        ];
    }

    /**
     * @return mixed[]
     */
    public function getLimiterCount(): array
    {
        $headers = $this->topologyManager->getHeadersForTopologyRunRequest();

        return $this->generatorBridge->getLimiterSnapshot($headers);
    }

    /**
     * @param string $from
     * @param string $to
     * @param int    $buckets
     *
     * @return mixed[]
     */
    public function getLimiterHistory(string $from, string $to, int $buckets): array
    {
        $dateFrom = new UTCDateTime(new DateTime($from));
        $dateTo   = new UTCDateTime(new DateTime($to));

        $rangeMs = max(1, (int) (string) $dateTo - (int) (string) $dateFrom);
        $binSize = max(60_000, (int) ceil($rangeMs / max(1, $buckets)));

        return [
            'limiter'  => $this->aggregateHistory(LimiterMetrics::class, $dateFrom, $dateTo, $binSize),
            'repeater' => $this->aggregateHistory(RepeaterMetrics::class, $dateFrom, $dateTo, $binSize),
        ];
    }

    /**
     * @param class-string $documentClass
     * @param UTCDateTime  $from
     * @param UTCDateTime  $to
     * @param int          $binSize
     *
     * @return mixed[]
     */
    private function aggregateHistory(string $documentClass, UTCDateTime $from, UTCDateTime $to, int $binSize): array
    {
        try {
            $builder = $this->metricsDm->createAggregationBuilder($documentClass);

            $builder
                ->match()
                ->field('fields.created')->gte($from)
                ->field('fields.created')->lt($to);

            $builder
                ->group()
                ->field('_id')
                ->dateTrunc('$fields.created', 'minute')
                ->field('countAtMinute')
                ->sum('$fields.messages');

            $fromMs = (int) (string) $from;

            $builder
                ->group()
                ->field('_id')
                ->toDate(
                    $builder->expr()->add(
                        $builder->expr()->toLong($from),
                        $builder->expr()->multiply(
                            $builder->expr()->floor(
                                $builder->expr()->divide(
                                    $builder->expr()->subtract(
                                        $builder->expr()->toLong('$_id'),
                                        $fromMs,
                                    ),
                                    $binSize,
                                ),
                            ),
                            $binSize,
                        ),
                    ),
                )
                ->field('count')
                ->max('$countAtMinute');

            $builder
                ->sort(['_id' => 'asc'])
                ->project()
                ->field('_id')
                ->expression(FALSE)
                ->field('created')
                ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$_id')
                ->field('count')
                ->round('$count');

            return $builder->execute()->toArray();
        } catch (Throwable) {
            return [];
        }
    }

}
