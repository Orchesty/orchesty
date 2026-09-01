<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;

/**
 * Class TopologyAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final class TopologyAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return class-string
     */
    protected function getDocumentClass(): string
    {
        return TopologyProgress::class;
    }

    /**
     * @return string[]
     */
    protected function getConditions(): array
    {
        return [
            'created' => 'startedAt',
            'status' => 'status',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'count' => 'count',
            'created' => 'created',
            'failedCount' => 'failedCount',
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
            ->sort(['startedAt' => 'desc'])
            ->group()
            ->field('_id')
            ->expression('$topologyId')
            ->field('topologyId')
            ->first('$topologyId')
            ->field('created')
            ->first('$startedAt')
            ->field('count')
            ->sum(1)
            ->field('failedCount')
            ->sum(
                $builder->expr()->cond(
                    $builder->expr()->or(
                        $builder->expr()->eq('$status', 'FAILED'),
                        $builder->expr()->eq('$status', 'TERMINATED'),
                    ),
                    1,
                    0,
                ),
            )
            ->field('status')
            ->first('$status');

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('created')
            ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$created')
            ->field('count')
            ->expression('$count')
            ->field('failedCount')
            ->expression('$failedCount')
            ->field('status')
            ->expression('$status');
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
            ->expression('$topologyId');
    }

}
