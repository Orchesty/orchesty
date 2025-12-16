<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Model;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\PipesFramework\Configurator\Model\Filters\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Logs\Document\Logs;

/**
 * Class LogsAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Logs\Model
 */
final class LogsAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return string
     * @phpstan-return class-string
     */
    protected function getDocumentClass(): string
    {
        return Logs::class;
    }

    /**
     * @return string[]
     */
    protected function filterCols(): array
    {
        return [
            'created' => 'ts',
            'message' => 'message',
            'nodeId' => 'pipes.node_id',
            'severity' => 'pipes.severity',
            'topologyId' => 'pipes.topology_id',
        ];
    }

    /**
     * @return string[]
     */
    protected function orderCols(): array
    {
        return [
            'created' => 'ts',
        ];
    }

    /**
     * @return mixed[]
     */
    protected function searchableCols(): array
    {
        return ['message'];
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
    ): void {
        $addConditionsCallback();
        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('nodeId')
            ->expression('$pipes.node_id')
            ->field('topologyId')
            ->expression('$pipes.topology_id')
            ->field('correlationId')
            ->expression('$pipes.correlation_id')
            ->field('created')
            ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$ts')
            ->field('severity')
            ->expression('$pipes.severity')
            ->field('message')
            ->expression('$message');
    }

}
