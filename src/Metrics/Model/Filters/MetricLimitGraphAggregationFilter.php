<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Configurator\Model\Filters\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;

/**
 * Class MetricLimitGraphAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricLimitGraphAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return string
     * @phpstan-return class-string
     */
    protected function getDocumentClass(): string
    {
        return LimiterMetrics::class;
    }

    /**
     * @return string[]
     */
    protected function filterCols(): array
    {
        return [
            'created' => 'fields.created',
        ];
    }

    /**
     * @return string[]
     */
    protected function orderCols(): array
    {
        return [
            'created' => 'created',
        ];
    }

    /**
     * @return mixed[]
     */
    protected function searchableCols(): array
    {
        return [];
    }

    /**
     * @return bool
     */
    protected function useBetterCount(): bool
    {
        return FALSE;
    }

    /**
     * @param Builder $builder
     * @param Closure $addConditionsCallback
     * @param Closure $addSortationsCallback
     * @param Closure $addPaginationCallback
     *
     * @return void
     */
    protected function configureAggregationBuilder(
        Builder $builder,
        Closure $addConditionsCallback,
        Closure $addSortationsCallback,
        Closure $addPaginationCallback,
    ): void
    {
        $addConditionsCallback();

        $builder
            ->group()
            ->field('_id')
            ->expression(
                $builder
                    ->expr()
                    ->field('hour')
                    ->expression(
                        $builder
                            ->expr()
                            ->dateTrunc(
                                '$fields.created',
                                'minute',
                                AggregationFilterUtils::getDateTruncBinSizeFromAggregationBuilder($builder),
                            ),
                    ),
            )
            ->field('count')
            ->expression($builder->expr()->avg('$fields.messages'))
            ->addFields()
            ->field('created')
            ->expression('$_id.hour');

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('_id')
            ->expression(FALSE)
            ->field('created')
            ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$created')
            ->field('count')
            ->round('$count');
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
            ->expression(
                $builder
                    ->expr()
                    ->dateTrunc(
                        '$fields.created',
                        'minute',
                        AggregationFilterUtils::getDateTruncBinSizeFromAggregationBuilder($builder),
                    ),
            );
    }

}
