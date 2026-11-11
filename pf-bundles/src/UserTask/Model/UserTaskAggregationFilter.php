<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UserTask\Model;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\UserTask\Document\UserTask;

/**
 * Class UserTaskAggregationFilter
 *
 * @package Hanaboso\PipesFramework\UserTask\Model
 */
final class UserTaskAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return class-string
     */
    protected function getDocumentClass(): string
    {
        return UserTask::class;
    }

    /**
     * @return string[]
     */
    protected function getConditions(): array
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
        return ['message'];
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
            ->field('body')
            ->expression('$message.body')
            ->field('headers')
            ->expression('$message.headers');
    }

}
