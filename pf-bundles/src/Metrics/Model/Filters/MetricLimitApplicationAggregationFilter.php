<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;

/**
 * Class MetricLimitApplicationAggregationFilter
 *
 * Per-application twin of {@see MetricLimitAggregationFilter}. Both filters use
 * the exact same per-minute summation algorithm as the headline filter
 * ({@see MetricLimitTotalAggregationFilter}); the only difference is the
 * grouping dimension:
 *
 *   - MetricLimitTotalAggregationFilter         → group by minute (cross-app peak)
 *   - MetricLimitApplicationAggregationFilter   → group by (application, minute)
 *   - MetricLimitAggregationFilter              → group by (node, minute)
 *
 * This guarantees `app.max ≤ headline.max` for every app, which the previous
 * "sum of per-node maxes" rollup in the FE could not (different nodes could
 * peak in different minutes, inflating the sum past the headline).
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricLimitApplicationAggregationFilter extends GridAggregationFilterAbstract
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

        // Materialise the truncated minute via $addFields (Doctrine ODM doesn't
        // expose `dateTrunc` on the expression builder, only on stage operators)
        // and only then build the composite `_id` from plain field references.
        $builder
            ->addFields()
            ->field('minute')
            ->dateTrunc('$fields.created', 'minute')
            ->group()
            ->field('_id')
            ->expression(
                $builder
                    ->expr()
                    ->field('applicationId')
                    ->expression('$tags.applicationId')
                    ->field('minute')
                    ->expression('$minute'),
            )
            ->field('countAtMinute')
            ->sum('$fields.messages')
            ->group()
            ->field('_id')
            ->expression('$_id.applicationId')
            ->field('maximumCount')
            ->max('$countAtMinute');

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('_id')
            ->expression(FALSE)
            ->field('applicationId')
            ->expression('$_id')
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

        // Cardinality of distinct applications — paginating per-app rollup
        // never needs the per-minute summation step.
        $builder
            ->group()
            ->field('_id')
            ->expression('$tags.applicationId');
    }

}
