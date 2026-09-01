<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;

/**
 * Class ProcessAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final class ProcessAggregationFilter extends GridAggregationFilterAbstract
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
            'source' => 'source',
            'status' => 'status',
            'topologyId' => 'topologyId',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'created' => 'startedAt',
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
        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->lookup('Logs')
            ->let(['processId' => '$_id'])
            ->pipeline([
                [
                    '$match' => [
                        '$expr' => [
                            '$and' => [
                                ['$eq' => ['$pipes.correlation_id', '$$processId']],
                                ['$eq' => ['$pipes.severity', 'error']],
                            ],
                        ],
                    ],
                ],
                [
                    '$project' => [
                        'message' => 1,
                    ],
                ],
            ])
            ->alias('logs')
            ->project()
            ->field('id')
            ->expression('$_id')
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('created')
            ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$created')
            ->field('status')
            ->expression('$status')
            ->field('duration')
            ->expression('$duration')
            ->field('source')
            ->expression('$source')
            ->field('messages')
            ->map('$logs', 'log', '$$log.message');
    }

}
