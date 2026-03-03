<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;

/**
 * Class MetricConnectorGraphAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricConnectorGraphAggregationFilter extends GridAggregationFilterAbstract
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
            'nodeId' => 'tags.node_id',
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
