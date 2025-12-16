<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Configurator\Model\Filters\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;

/**
 * Class MetricConnectorGraphAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricConnectorGraphAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return string
     * @phpstan-return class-string
     */
    protected function getDocumentClass(): string
    {
        return ConnectorsMetrics::class;
    }

    /**
     * @return string[]
     */
    protected function filterCols(): array
    {
        return [
            'created' => 'fields.created',
            'nodeId' => 'tags.node_id',
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
        return [];
    }

    /**
     * @return bool
     */
    protected function useBetterCount(): bool
    {
        return FALSE;
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
                                '$fields.created',
                                'minute',
                                AggregationFilterUtils::getDateTruncBinSizeFromAggregationBuilder($builder),
                            ),
                    ),
            )
            ->field('status200')
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
            ->field('status400')
            ->expression(
                $builder->expr()->sum(
                    $builder->expr()->cond(
                        $builder->expr()->and(
                            $builder->expr()->gte('$fields.response_code', 400),
                            $builder->expr()->lte('$fields.response_code', 499),
                        ),
                        1,
                        0,
                    ),
                ),
            )
            ->field('status500')
            ->expression(
                $builder->expr()->sum(
                    $builder->expr()->cond(
                        $builder->expr()->and(
                            $builder->expr()->gte('$fields.response_code', 500),
                            $builder->expr()->lte('$fields.response_code', 599),
                        ),
                        1,
                        0,
                    ),
                ),
            )
            ->addFields()
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
            ->field('status200')
            ->expression('$status200')
            ->field('status400')
            ->expression('$status400')
            ->field('status500')
            ->expression('$status500');
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
                    ->dateTrunc(
                        '$fields.created',
                        'minute',
                        AggregationFilterUtils::getDateTruncBinSizeFromAggregationBuilder($builder),
                    ),
            );
    }

}
