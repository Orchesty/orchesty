<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;

/**
 * Class ProcessGraphAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final class ProcessGraphAggregationFilter extends GridAggregationFilterAbstract
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
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'created' => 'created',
            'topologyId' => 'topologyId',
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
                                '$created',
                                'minute',
                                AggregationFilterUtils::getDateTruncBinSizeFromAggregationBuilder($builder),
                            ),
                    )
                    ->field('topologyId')
                    ->expression('$topologyId'),
            )
            ->field('success')
            ->expression(
                $builder->expr()->sum(
                    $builder->expr()->cond(
                        $builder->expr()->eq('$nok', 0),
                        1,
                        0,
                    ),
                ),
            )
            ->field('failed')
            ->expression(
                $builder->expr()->sum(
                    $builder->expr()->cond(
                        $builder->expr()->ne('$nok', 0),
                        1,
                        0,
                    ),
                ),
            )
            ->addFields()
            ->field('topologyId')
            ->expression('$_id.topologyId')
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
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('success')
            ->expression('$success')
            ->field('failed')
            ->expression('$failed');
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
                    ->field('hour')
                    ->expression(
                        $builder
                        ->expr()
                        ->dateTrunc(
                            '$created',
                            'minute',
                            AggregationFilterUtils::getDateTruncBinSizeFromAggregationBuilder($builder),
                        ),
                    )
                    ->field('topologyId')
                    ->expression('$topologyId'),
            );
    }

}
