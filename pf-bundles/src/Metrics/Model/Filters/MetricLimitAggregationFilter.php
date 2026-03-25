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
            'count' => 'count',
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
            ->sort(['fields.created' => 'asc'])
            ->group()
            ->field('_id')
            ->expression('$tags.nodeName')
            ->field('nodeId')
            ->first('$tags.nodeId')
            ->field('topologyId')
            ->first('$tags.topologyId')
            ->field('applicationId')
            ->first('$tags.applicationId')
            ->field('count')
            ->last(
                $builder->expr()->cond(
                    $builder->expr()->gt(
                        $builder->expr()->subtract('$$NOW', '$fields.created'),
                        90_000,
                    ),
                    0,
                    '$fields.messages',
                ),
            )
            ->field('maximumCount')
            ->max('$fields.messages')
            ->match()
            ->field('count')
            ->gt(0);

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
            ->ifNull('$count', 0)
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

        $builder
            ->sort(['fields.created' => 'asc'])
            ->group()
            ->field('_id')
            ->expression('$tags.nodeName')
            ->field('count')
            ->last(
                $builder->expr()->cond(
                    $builder->expr()->gt(
                        $builder->expr()->subtract('$$NOW', '$fields.created'),
                        90_000,
                    ),
                    0,
                    '$fields.messages',
                ),
            )
            ->match()
            ->field('count')
            ->gt(0);
    }

}
