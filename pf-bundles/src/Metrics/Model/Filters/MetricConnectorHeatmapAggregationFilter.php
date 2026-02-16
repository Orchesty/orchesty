<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;

/**
 * Class MetricConnectorHeatmapAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricConnectorHeatmapAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return class-string
     */
    protected function getDocumentClass(): string
    {
        return ConnectorsMetrics::class;
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
            'nodeId' => 'nodeId',
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
                                '$fields.created',
                                'minute',
                                AggregationFilterUtils::getDateTruncBinSizeFromAggregationBuilder($builder),
                            ),
                    )
                    ->field('nodeId')
                    ->expression('$tags.node_id'),
            )
            ->field('applicationId')
            ->first('$tags.application_id')
            ->field('success')
            ->expression(
                $builder->expr()->sum(
                    $builder->expr()->cond(
                        $builder->expr()->and(
                            $builder->expr()->gte('$fields.response_code', 200),
                            $builder->expr()->lte('$fields.response_code', 399),
                        ),
                        1,
                        0,
                    ),
                ),
            )
            ->field('failed')
            ->expression(
                $builder->expr()->sum(
                    $builder->expr()->cond(
                        $builder->expr()->gte('$fields.response_code', 400),
                        1,
                        0,
                    ),
                ),
            )
            ->addFields()
            ->field('nodeId')
            ->expression('$_id.nodeId')
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
            ->field('nodeId')
            ->expression('$nodeId')
            ->field('applicationId')
            ->expression('$applicationId')
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
                                '$fields.created',
                                'minute',
                                AggregationFilterUtils::getDateTruncBinSizeFromAggregationBuilder($builder),
                            ),
                    )
                    ->field('nodeId')
                    ->expression('$tags.node_id'),
            );
    }

}
