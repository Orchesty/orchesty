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
                                            $builder->expr()->toLong('$created'),
                                            $builder->expr()->toLong($gte),
                                        ),
                                        $binSize,
                                    ),
                                ),
                                $binSize,
                            ),
                        ),
                    )
                    ->field('topologyId')
                    ->expression('$topologyId'),
            )
            ->field('success')
            ->sum(
                $builder->expr()->cond(
                    $builder->expr()->eq('$nok', 0),
                    1,
                    0,
                ),
            )
            ->field('failed')
            ->sum(
                $builder->expr()->cond(
                    $builder->expr()->ne('$nok', 0),
                    1,
                    0,
                ),
            )
            ->addFields()
            ->field('topologyId')
            ->expression('$_id.topologyId')
            ->field('created')
            ->expression('$_id.hour');

        if ($densifyStart !== NULL && $densifyEnd !== NULL) {
            $builder
                ->densify('created')
                ->partitionByFields('topologyId')
                ->range([$densifyStart, $densifyEnd], $binSize, 'millisecond')
                ->fill()
                ->sortBy('created', 'asc')
                ->output()
                ->field('success')
                ->value(0)
                ->field('failed')
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
                                            $builder->expr()->toLong('$created'),
                                            $builder->expr()->toLong($gte),
                                        ),
                                        $binSize,
                                    ),
                                ),
                                $binSize,
                            ),
                        ),
                    )
                    ->field('topologyId')
                    ->expression('$topologyId'),
            );

        if ($densifyStart !== NULL && $densifyEnd !== NULL) {
            $builder
                ->addFields()
                ->field('topologyId')
                ->expression('$_id.topologyId')
                ->field('created')
                ->expression('$_id.hour')
                ->densify('created')
                ->partitionByFields('topologyId')
                ->range([$densifyStart, $densifyEnd], $binSize, 'millisecond');
        }
    }

}
