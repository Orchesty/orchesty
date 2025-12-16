<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Model;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\PipesFramework\Configurator\Model\Filters\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;

/**
 * Class UserTaskAggregationFilter
 *
 * @package Hanaboso\PipesFramework\UserTask\Model
 */
final class UserTaskAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return string
     * @phpstan-return class-string
     */
    protected function getDocumentClass(): string
    {
        return UserTask::class;
    }

    /**
     * @return string[]
     */
    protected function filterCols(): array
    {
        return [
            'correlationId' => 'correlationId',
            'created' => 'created',
            'message' => 'message.headers.result-message',
            'nodeId' => 'nodeId',
            'topologyId' => 'topologyId',
            'type' => 'type',
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
            ->expression('$nodeId')
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('correlationId')
            ->expression('$correlationId')
            ->field('created')
            ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$created')
            ->field('message')
            ->ifNull('$message.headers.result-message', NULL);
    }

}
