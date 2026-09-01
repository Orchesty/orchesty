<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Metrics\Document\HourlyConnectorsAggregate;

/**
 * Class MetricHourlyConnectorOverviewAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricHourlyConnectorOverviewAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return class-string
     */
    protected function getDocumentClass(): string
    {
        return HourlyConnectorsAggregate::class;
    }

    /**
     * @return string[]
     */
    protected function getConditions(): array
    {
        return [
            'applicationId' => 'application_id',
            'created' => 'bucket',
            'nodeId' => 'node_id',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'count' => 'count',
            'duration' => 'duration',
            'lastStatus' => 'lastStatus',
            'status400' => 'status400',
            'status500' => 'status500',
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
            ->sort(['last_created' => 'asc'])
            ->group()
            ->field('_id')
            ->expression('$node_name')
            ->field('nodeId')
            ->last('$node_id')
            ->field('topologyId')
            ->last('$topology_id')
            ->field('applicationId')
            ->last('$application_id')
            ->field('count')
            ->sum('$count')
            ->field('durationSum')
            ->sum('$duration_sum')
            ->field('status400')
            ->sum('$status400')
            ->field('status500')
            ->sum('$status500')
            ->field('lastStatus')
            ->last('$last_response_code')
            ->addFields()
            ->field('duration')
            ->round($builder->expr()->divide('$durationSum', '$count'));

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('_id')
            ->expression(FALSE)
            ->field('nodeId')
            ->expression('$nodeId')
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('applicationId')
            ->expression('$applicationId')
            ->field('count')
            ->expression('$count')
            ->field('duration')
            ->expression('$duration')
            ->field('status400')
            ->expression('$status400')
            ->field('status500')
            ->expression('$status500')
            ->field('lastStatus')
            ->expression('$lastStatus');
    }

    /**
     * @param Builder $builder
     * @param Closure $addConditionsCallback
     *
     * @return void
     */
    protected function configureCountAggregationBuilder(Builder $builder, Closure $addConditionsCallback): void
    {
        $addConditionsCallback();

        $builder
            ->group()
            ->field('_id')
            ->expression('$node_name');
    }

}
