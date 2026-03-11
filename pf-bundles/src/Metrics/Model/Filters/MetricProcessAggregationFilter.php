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

    private bool $lastRunMode = FALSE;

    /**
     * @param bool $lastRunMode
     *
     * @return void
     */
    public function setLastRunMode(bool $lastRunMode): void
    {
        $this->lastRunMode = $lastRunMode;
    }

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

        if ($this->lastRunMode) {
            $builder->sort(['fields.created' => 'desc']);
        }

        $group = $builder
            ->group()
            ->field('_id')
            ->expression('$tags.node_id')
            ->field('topologyId')
            ->first('$tags.topology_id')
            ->field('duration');

        $this->lastRunMode
            ? $group->first('$fields.worker_duration')
            : $group->avg('$fields.worker_duration');

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
