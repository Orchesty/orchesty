<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Metrics\Document\UserTaskMetrics;

/**
 * Class MetricUserTaskTotalAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricUserTaskTotalAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return class-string
     */
    protected function getDocumentClass(): string
    {
        return UserTaskMetrics::class;
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
            'created' => 'created',
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
            ->expression($builder->expr()->round($builder->expr()->ifNull('$count', 0)))
            ->field('previousCount')
            ->expression($builder->expr()->round($builder->expr()->ifNull('$previousCount', 0)));
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
