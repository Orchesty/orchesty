<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricConnectorAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricConnectorGraphAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricConnectorHeatmapAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricConnectorOverviewAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricLimitAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricLimitGraphAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricLimitTotalAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricProcessAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricRequestAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricUserTaskAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricUserTaskGraphAggregationFilter;
use Hanaboso\PipesFramework\Metrics\Model\Filters\MetricUserTaskTotalAggregationFilter;

/**
 * Trait MongoTestTrait
 *
 * @package PipesFrameworkTests
 */
trait MongoTestTrait
{

    /**
     * @param Topology $topology
     * @param Node     $node
     */
    abstract protected function setFakeData(Topology $topology, Node $node): void;

    /**
     * @return Topology
     * @throws Exception
     */
    private function createTopology(): Topology
    {
        $topo = new Topology();
        $topo->setName(uniqid());
        $this->dm->persist($topo);
        $this->dm->flush();

        return $topo;
    }

    /**
     * @param Topology $topology
     *
     * @return Node
     * @throws Exception
     */
    private function createNode(Topology $topology): Node
    {
        $node = new Node();
        $node
            ->setTopology($topology->getId())
            ->setName(uniqid());
        $this->dm->persist($node);
        $this->dm->flush();

        return $node;
    }

    /**
     * @return MongoMetricsManager
     */
    private function getManager(): MongoMetricsManager
    {
        /** @var string $nodeTable */
        $nodeTable = self::getContainer()->getParameter('mongodb.node_table');
        /** @var string $fpmTable */
        $fpmTable = self::getContainer()->getParameter('mongodb.monolith_table');
        /** @var string $connTable */
        $connTable = self::getContainer()->getParameter('mongodb.connector_table');
        /** @var string $rabbitTable */
        $rabbitTable = self::getContainer()->getParameter('mongodb.rabbit_table');
        /** @var string $counterTable */
        $counterTable = self::getContainer()->getParameter('mongodb.counter_table');
        /** @var string $counterTable */
        $consumerTable = self::getContainer()->getParameter('mongodb.rabbit_consumer_table');
        /** @var DocumentManager $metricsDm */
        $metricsDm = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');
        /** @var MetricConnectorOverviewAggregationFilter $metricConnectorOverviewAggregationFilter */
        $metricConnectorOverviewAggregationFilter = self::getContainer()->get(
            'hbpf.metric-connector-overview.aggregation-filter',
        );
        /** @var MetricConnectorAggregationFilter $metricConnectorAggregationFilter */
        $metricConnectorAggregationFilter = self::getContainer()->get('hbpf.metric-connector.aggregation-filter');
        /** @var MetricConnectorGraphAggregationFilter $metricConnectorGraphAggregationFilter */
        $metricConnectorGraphAggregationFilter = self::getContainer()->get(
            'hbpf.metric-connector-graph.aggregation-filter',
        );
        /** @var MetricRequestAggregationFilter $metricRequestAggregationFilter */
        $metricRequestAggregationFilter = self::getContainer()->get('hbpf.metric-request.aggregation-filter');
        /** @var MetricProcessAggregationFilter $metricProcessAggregationFilter */
        $metricProcessAggregationFilter = self::getContainer()->get('hbpf.metric-process.aggregation-filter');
        /** @var MetricLimitAggregationFilter $metricLimitAggregationFilter */
        $metricLimitAggregationFilter = self::getContainer()->get('hbpf.metric-limit.aggregation-filter');
        /** @var MetricLimitTotalAggregationFilter $metricLimitTotalAggregationFilter */
        $metricLimitTotalAggregationFilter = self::getContainer()->get('hbpf.metric-limit-total.aggregation-filter');
        /** @var MetricLimitGraphAggregationFilter $metricLimitGraphAggregationFilter */
        $metricLimitGraphAggregationFilter = self::getContainer()->get('hbpf.metric-limit-graph.aggregation-filter');
        /** @var MetricUserTaskAggregationFilter $metricUserTaskAggregationFilter */
        $metricUserTaskAggregationFilter = self::getContainer()->get('hbpf.metric-user-task.aggregation-filter');
        /** @var MetricUserTaskTotalAggregationFilter $metricUserTaskTotalAggregationFilter */
        $metricUserTaskTotalAggregationFilter = self::getContainer()->get(
            'hbpf.metric-user-task-total.aggregation-filter',
        );
        /** @var MetricUserTaskGraphAggregationFilter $metricUserTaskGraphAggregationFilter */
        $metricUserTaskGraphAggregationFilter = self::getContainer()->get(
            'hbpf.metric-user-task-graph.aggregation-filter',
        );
        /** @var MetricConnectorHeatmapAggregationFilter $metricConnectorHeatmapAggregationFilter */
        $metricConnectorHeatmapAggregationFilter = self::getContainer()->get(
            'hbpf.metric-connector-heatmap.aggregation-filter',
        );

        return new MongoMetricsManager(
            $this->dm,
            $nodeTable,
            $fpmTable,
            $rabbitTable,
            $counterTable,
            $connTable,
            $metricsDm,
            $consumerTable,
            $metricConnectorOverviewAggregationFilter,
            $metricConnectorAggregationFilter,
            $metricConnectorGraphAggregationFilter,
            $metricRequestAggregationFilter,
            $metricProcessAggregationFilter,
            $metricLimitAggregationFilter,
            $metricLimitTotalAggregationFilter,
            $metricLimitGraphAggregationFilter,
            $metricUserTaskAggregationFilter,
            $metricUserTaskTotalAggregationFilter,
            $metricUserTaskGraphAggregationFilter,
            $metricConnectorHeatmapAggregationFilter,
        );
    }

}
