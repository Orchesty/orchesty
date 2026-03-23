<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Model\Filters\AggregationFilterUtils;
use Hanaboso\PipesFramework\Metrics\Document\LimiterMetrics;
use MongoDB\BSON\UTCDateTime;

/**
 * Class MetricLimitTotalAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricLimitTotalAggregationFilter extends GridAggregationFilterAbstract
{

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

        [$gte, $lte] = AggregationFilterUtils::getDates($builder);

        $builder
            ->group()
            ->field('_id')
            ->dateTrunc('$fields.created', 'minute')
            ->field('countAtMinute')
            ->sum('$fields.messages')
            ->addFields()
            ->field('created')
            ->expression('$_id');

        if ($gte !== NULL && $lte !== NULL) {
            $gteMs       = (int) (string) $gte;
            $lteMs       = (int) (string) $lte;
            $gteMinuteMs = $gteMs - $gteMs % 60_000;
            $lteMinuteMs = $lteMs - $lteMs % 60_000;

            $builder
                ->densify('created')
                ->range([new UTCDateTime($gteMinuteMs), new UTCDateTime($lteMinuteMs + 1)], 60_000, 'millisecond')
                ->fill()
                ->sortBy('created', 'asc')
                ->output()
                ->field('countAtMinute')
                ->locf();
        }

        $builder
            ->sort(['created' => 'asc'])
            ->group()
            ->field('_id')
            ->expression(NULL)
            ->field('count')
            ->last('$countAtMinute')
            ->field('maximumCount')
            ->max('$countAtMinute');

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('_id')
            ->expression(FALSE)
            ->field('count')
            ->expression('$count')
            ->field('maximumCount')
            ->expression('$maximumCount');
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
            ->expression(NULL);
    }

}
