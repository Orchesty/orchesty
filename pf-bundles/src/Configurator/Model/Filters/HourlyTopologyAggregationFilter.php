<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Document\HourlyMultiCounterAggregate;

/**
 * Class HourlyTopologyAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final class HourlyTopologyAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return class-string
     */
    protected function getDocumentClass(): string
    {
        return HourlyMultiCounterAggregate::class;
    }

    /**
     * @return string[]
     */
    protected function getConditions(): array
    {
        return [
            'created' => 'bucket',
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
            ->sort(['last_created' => 'asc'])
            ->group()
            ->field('_id')
            ->expression('$topology_id')
            ->field('topologyId')
            ->first('$topology_id')
            ->field('created')
            ->last('$last_created')
            ->field('count')
            ->sum('$count')
            ->field('failedCount')
            ->sum('$failed')
            ->field('status')
            ->last('$last_status');

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
    protected function configureCountAggregationBuilder(Builder $builder, Closure $addConditionsCallback): void
    {
        $addConditionsCallback();

        $builder
            ->group()
            ->field('_id')
            ->expression('$topology_id');
    }

}
