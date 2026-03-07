<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;

/**
 * Class MetricLimitGraphAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricLimitGraphAggregationFilter extends GridAggregationFilterAbstract
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
        return LimiterMetrics::class;
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
            ->dateTrunc('$fields.created', 'minute')
            ->field('countAtMinute')
            ->sum('$fields.messages')
            ->group()
            ->field('_id')
            ->expression(
                $builder->expr()->toDate(
                    $builder->expr()->add(
                        $builder->expr()->toLong($gte),
                        $builder->expr()->multiply(
                            $builder->expr()->floor(
                                $builder->expr()->divide(
                                    $builder->expr()->subtract(
                                        $builder->expr()->toLong('$_id'),
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
            ->field('count')
            ->max('$countAtMinute')
            ->addFields()
            ->field('created')
            ->expression('$_id');

        if ($densifyStart !== NULL && $densifyEnd !== NULL) {
            $builder
                ->densify('created')
                ->range([$densifyStart, $densifyEnd], $binSize, 'millisecond')
                ->fill()
                ->sortBy('created', 'asc')
                ->output()
                ->field('count')
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
            ->field('count')
            ->round('$count');
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
            ->dateTrunc('$fields.created', 'minute')
            ->group()
            ->field('_id')
            ->expression(
                $builder->expr()->toDate(
                    $builder->expr()->add(
                        $builder->expr()->toLong($gte),
                        $builder->expr()->multiply(
                            $builder->expr()->floor(
                                $builder->expr()->divide(
                                    $builder->expr()->subtract(
                                        $builder->expr()->toLong('$_id'),
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
