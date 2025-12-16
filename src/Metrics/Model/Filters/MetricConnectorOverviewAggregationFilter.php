<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Hanaboso\PipesFramework\Configurator\Model\Filters\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MetricConnectorOverviewAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricConnectorOverviewAggregationFilter extends GridAggregationFilterAbstract
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
            'applicationId' => 'tags.application_id',
            'correlationId' => 'tags.correlation_id',
            'created' => 'fields.created',
            'nodeId' => 'tags.node_id',
            'status' => 'status',
            'topologyId' => 'tags.topology_id',
        ];
    }

    /**
     * @return string[]
     */
    protected function orderCols(): array
    {
        return [
            'duration' => 'duration',
        ];
    }


    /**
     * @return mixed[]
     */
    protected function configFilterColsCallbacks(): array
    {
        return [
            'status' => static function (
                Builder $builder,
                mixed $value,
                string $name,
                Expr $expr,
                string $operator,
            ): void {
                $builder;
                $operator;
                $name;

                match ($value[0]) {
                    'COMPLETED' => $expr->addAnd($builder->matchExpr()->field('fields.response_code')->lte(399)),
                    'FAILED' => $expr->addAnd($builder->matchExpr()->field('fields.response_code')->gte(400)),
                    default => throw new LogicException(
                        sprintf('Unknown status value `%s`.', $value[0]),
                        Response::HTTP_BAD_REQUEST,
                    ),
                };
            },
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
    ): void {
        $addConditionsCallback();

        $builder
            ->sort(['fields.created' => 'asc'])
            ->group()
            ->field('_id')
            ->expression('$tags.node_id')
            ->field('topologyId')
            ->last('$tags.topology_id')
            ->field('applicationId')
            ->last('$tags.application_id')
            ->field('count')
            ->expression($builder->expr()->sum(1))
            ->field('duration')
            ->expression($builder->expr()->avg('$fields.sent_request_total_duration'))
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
            ->field('lastStatus')
            ->last('$fields.response_code');

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('nodeId')
            ->expression('$_id')
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('applicationId')
            ->expression('$applicationId')
            ->field('count')
            ->expression('$count')
            ->field('duration')
            ->expression($builder->expr()->round('$duration'))
            ->field('status400')
            ->expression('$status400')
            ->field('status500')
            ->expression('$status500')
            ->field('lastStatus')
            ->expression('$lastStatus');
    }

    /**
     * @param Builder $builder
     * @param Closure $addConditionsCallback
     *
     * @return void
     */
    protected function configureCountAggregationBuilder(Builder $builder, Closure $addConditionsCallback): void {
        $addConditionsCallback();

        $builder
            ->group()
            ->field('_id')
            ->expression('$tags.node_id');
    }

}
