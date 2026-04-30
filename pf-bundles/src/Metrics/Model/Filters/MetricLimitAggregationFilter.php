<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;

/**
 * Class MetricLimitAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricLimitAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return class-string
     */
    protected function getDocumentClass(): string
    {
        return LimiterMetrics::class;
    }

    /**
     * @return string[]
     */
    protected function getConditions(): array
    {
        return [
            'created' => 'fields.created',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'maximumCount' => 'maximumCount',
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

        // Mirror the per-minute aggregation used by MetricLimitTotalAggregationFilter
        // so that the per-node "Max" in the grid is computed by the exact same
        // algorithm as the "Max" headline above the chart, only without the
        // cross-node sum. Concretely: bucket every metric record into the
        // minute it belongs to, sum `fields.messages` within that bucket, and
        // then take the per-node max across those minute buckets. This makes
        // SUM(grid.max per node) >= card.max (different peak minutes per node)
        // a documented expectation, not a bug.
        //
        // Implementation note: Doctrine ODM doesn't expose `dateTrunc` on the
        // expression builder (only on a stage/field accumulator), so we materialise
        // the truncated minute via `$addFields` first and only then build the
        // composite `_id` from plain field references — which is well-supported.
        $builder
            ->addFields()
            ->field('minute')
            ->dateTrunc('$fields.created', 'minute')
            ->group()
            ->field('_id')
            ->expression(
                $builder
                    ->expr()
                    ->field('nodeName')
                    ->expression('$tags.nodeName')
                    ->field('minute')
                    ->expression('$minute'),
            )
            ->field('countAtMinute')
            ->sum('$fields.messages')
            ->field('nodeId')
            ->first('$tags.nodeId')
            ->field('topologyId')
            ->first('$tags.topologyId')
            ->field('applicationId')
            ->first('$tags.applicationId')
            ->group()
            ->field('_id')
            ->expression('$_id.nodeName')
            ->field('maximumCount')
            ->max('$countAtMinute')
            ->field('nodeId')
            ->first('$nodeId')
            ->field('topologyId')
            ->first('$topologyId')
            ->field('applicationId')
            ->first('$applicationId');

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
            ->field('maximumCount')
            ->ifNull('$maximumCount', 0);
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

        // Count aggregation only needs the cardinality of distinct nodes — the
        // per-minute summation step would just inflate the work, so collapse
        // straight to one document per nodeName.
        $builder
            ->group()
            ->field('_id')
            ->expression('$tags.nodeName');
    }

}
