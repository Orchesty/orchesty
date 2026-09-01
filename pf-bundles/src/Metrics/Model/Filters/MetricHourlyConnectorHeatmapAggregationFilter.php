<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Metrics\Document\HourlyConnectorsAggregate;

/**
 * Class MetricHourlyConnectorHeatmapAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricHourlyConnectorHeatmapAggregationFilter extends GridAggregationFilterAbstract
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
        return HourlyConnectorsAggregate::class;
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
                                $builder->expr()->floor(
                                    $builder->expr()->divide(
                                        $builder->expr()->subtract(
                                            $builder->expr()->toLong('$bucket'),
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
                    ->expression('$node_id'),
            )
            ->field('applicationId')
            ->first('$application_id')
            ->field('success')
            ->sum('$status200')
            ->field('failed')
            ->sum($builder->expr()->add('$status400', '$status500'))
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
                ->locf()
                ->match()
                ->field('nodeId')
                ->notEqual(NULL);
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
                                $builder->expr()->floor(
                                    $builder->expr()->divide(
                                        $builder->expr()->subtract(
                                            $builder->expr()->toLong('$bucket'),
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
                    ->expression('$node_id'),
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
                ->range([$densifyStart, $densifyEnd], $binSize, 'millisecond')
                ->match()
                ->field('nodeId')
                ->notEqual(NULL);
        }
    }

}
