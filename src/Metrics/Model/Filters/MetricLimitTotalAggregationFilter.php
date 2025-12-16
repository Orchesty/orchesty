<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Configurator\Model\Filters\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;

/**
 * Class MetricLimitTotalAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricLimitTotalAggregationFilter extends GridAggregationFilterAbstract
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
        $middleTime = AggregationFilterUtils::getMiddleTimeFromAggregationBuilder($builder);

        $builder
            ->group()
            ->field('_id')
            ->expression(NULL)
            ->field('count')
            ->expression(
                $builder->expr()->avg(
                    $builder->expr()->cond(
                        $builder->expr()->gte('$fields.created', $middleTime),
                        '$fields.messages',
                        NULL,
                    ),
                ),
            )
            ->field('previousCount')
            ->expression(
                $builder->expr()->avg(
                    $builder->expr()->cond(
                        $builder->expr()->lte('$fields.created', $middleTime),
                        '$fields.messages',
                        NULL,
                    ),
                ),
            );

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('_id')
            ->expression(FALSE)
            ->field('count')
            ->round('$count')
            ->field('previousCount')
            ->round('$previousCount');
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
            ->expression(NULL);
    }

}
