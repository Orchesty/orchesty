<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Metrics\Document\BridgesMetrics;

/**
 * Class MetricProcessAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricProcessAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return class-string
     */
    protected function getDocumentClass(): string
    {
        return BridgesMetrics::class;
    }

    /**
     * @return string[]
     */
    protected function getConditions(): array
    {
        return [
            'created' => 'fields.created',
            'topologyId' => 'tags.topology_id',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'duration' => 'duration',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSearch(): array
    {
        return [];
    }

    /**
     * @param Builder         $builder
     * @param Closure(): void $addConditionsCallback
     * @param Closure(): void $addSortationsCallback
     * @param Closure(): void $addPaginationCallback
     *
     * @return void
     */
    protected function configureAggregationBuilder(
        Builder $builder,
        Closure $addConditionsCallback,
        Closure $addSortationsCallback,
        Closure $addPaginationCallback,
    ): void {
        $addConditionsCallback();

        $builder
            ->sort(['tags.node_id' => 1, 'fields.created' => -1])
            ->group()
            ->field('_id')
            ->expression('$tags.node_id')
            ->field('topologyId')
            ->first('$tags.topology_id')
            ->field('lastCorrelationId')
            ->first('$tags.correlation_id')
            ->lookup(BridgesMetrics::class)
            ->let(['nodeId' => '$_id', 'correlationId' => '$lastCorrelationId'])
            ->pipeline([
                [
                    '$match' => [
                        '$expr' => [
                            '$and' => [
                                ['$eq' => ['$tags.node_id', '$$nodeId']],
                                ['$eq' => ['$tags.correlation_id', '$$correlationId']],
                            ],
                        ],
                    ],
                ],
                [
                    '$group' => [
                        'duration' => ['$avg' => '$fields.worker_duration'],
                        '_id' => NULL,
                    ],
                ],
            ])
            ->alias('lastRun')
            ->addFields()
            ->field('duration')
            ->expression($builder->expr()->arrayElemAt('$lastRun.duration', 0));

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('nodeId')
            ->expression('$_id')
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('duration')
            ->round('$duration');
    }

    /**
     * @param Builder $builder
     * @param Closure $addConditionsCallback
     *
     * @return void
     */
    protected function configureCountAggregationBuilder(Builder $builder, Closure $addConditionsCallback): void {
        $addConditionsCallback();

        $builder
            ->group()
            ->field('_id')
            ->expression('$tags.node_id');
    }

}
