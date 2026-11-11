<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Metrics\Document\HourlyConnectorsAggregate;

/**
 * Class MetricHourlyConnectorGraphAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricHourlyConnectorGraphAggregationFilter extends GridAggregationFilterAbstract
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
            'nodeId' => 'node_id',
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
                    ),
            )
            ->field('status200')
            ->sum('$status200')
            ->field('status400')
            ->sum('$status400')
            ->field('status500')
            ->sum('$status500')
            ->addFields()
            ->field('created')
            ->expression('$_id.hour');

        if ($densifyStart !== NULL && $densifyEnd !== NULL) {
            $builder
                ->densify('created')
                ->range([$densifyStart, $densifyEnd], $binSize, 'millisecond')
                ->fill()
                ->sortBy('created', 'asc')
                ->output()
                ->field('status200')
                ->value(0)
                ->field('status400')
                ->value(0)
                ->field('status500')
                ->value(0);
        }

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
                    ),
            );

        if ($densifyStart !== NULL && $densifyEnd !== NULL) {
            $builder
                ->addFields()
                ->field('created')
                ->expression('$_id')
                ->densify('created')
                ->range([$densifyStart, $densifyEnd], $binSize, 'millisecond');
        }
    }

}
