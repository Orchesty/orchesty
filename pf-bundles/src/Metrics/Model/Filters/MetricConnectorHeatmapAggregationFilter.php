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

    private int $bucketCount = 0;

    /**
     * @param int $buckets
     *
     * @return void
     */
    public function setBucketCount(int $buckets): void
    {
        $this->bucketCount = $buckets;
    }

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

        [
            $binSize,
            $gte,
            $densifyStart,
            $densifyEnd,
        ] = AggregationFilterUtils::getDensifyBinSizeAndRangeFromAggregationBuilder($builder, $this->bucketCount);

        $builder
            ->group()
            ->field('_id')
            ->expression(
                $builder
                    ->expr()
                    ->field('hour')
                    ->toDate(
                        $builder->expr()->add(
                            $builder->expr()->toLong($gte),
                            $builder->expr()->multiply(
                                $builder->expr()->ceil(
                                    $builder->expr()->divide(
                                        $builder->expr()->subtract(
                                            $builder->expr()->toLong('$fields.created'),
                                            $builder->expr()->toLong($gte),
                                        ),
                                        $binSize,
                                    ),
                                ),
                                $binSize,
                            ),
                        ),
                    )
                    ->field('nodeId')
                    ->expression('$tags.node_id'),
            )
            ->field('applicationId')
            ->first('$tags.application_id')
            ->field('success')
            ->sum(
                $builder->expr()->cond(
                    $builder->expr()->and(
                        $builder->expr()->gte('$fields.response_code', 200),
                        $builder->expr()->lte('$fields.response_code', 399),
                    ),
                    1,
                    0,
                ),
            )
            ->field('failed')
            ->sum(
                $builder->expr()->cond(
                    $builder->expr()->gte('$fields.response_code', 400),
                    1,
                    0,
                ),
            )
            ->addFields()
            ->field('nodeId')
            ->expression('$_id.nodeId')
            ->field('created')
            ->expression('$_id.hour');

        if ($densifyStart !== NULL && $densifyEnd !== NULL) {
            $builder
                ->densify('created')
                ->partitionByFields('nodeId')
                ->range([$densifyStart, $densifyEnd], $binSize, 'millisecond')
                ->fill()
                ->partitionByFields('nodeId')
                ->sortBy('created', 'asc')
                ->output()
                ->field('success')
                ->value(0)
                ->field('failed')
                ->value(0)
                ->field('applicationId')
                ->locf()
                ->fill()
                ->partitionByFields('nodeId')
                ->sortBy('created', 'desc')
                ->output()
                ->field('applicationId')
                ->locf();
        }

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

        [
            $binSize,
            $gte,
            $densifyStart,
            $densifyEnd,
        ] = AggregationFilterUtils::getDensifyBinSizeAndRangeFromAggregationBuilder($builder, $this->bucketCount);

        $builder
            ->group()
            ->field('_id')
            ->expression(
                $builder
                    ->expr()
                    ->field('hour')
                    ->toDate(
                        $builder->expr()->add(
                            $builder->expr()->toLong($gte),
                            $builder->expr()->multiply(
                                $builder->expr()->ceil(
                                    $builder->expr()->divide(
                                        $builder->expr()->subtract(
                                            $builder->expr()->toLong('$fields.created'),
                                            $builder->expr()->toLong($gte),
                                        ),
                                        $binSize,
                                    ),
                                ),
                                $binSize,
                            ),
                        ),
                    )
                    ->field('nodeId')
                    ->expression('$tags.node_id'),
            );

        if ($densifyStart !== NULL && $densifyEnd !== NULL) {
            $builder
                ->addFields()
                ->field('nodeId')
                ->expression('$_id.nodeId')
                ->field('created')
                ->expression('$_id.hour')
                ->densify('created')
                ->partitionByFields('nodeId')
                ->range([$densifyStart, $densifyEnd], $binSize, 'millisecond');
        }
    }

}
