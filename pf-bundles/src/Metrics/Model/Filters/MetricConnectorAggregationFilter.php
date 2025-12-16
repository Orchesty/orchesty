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
 * Class MetricConnectorAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Metrics\Model\Filters
 */
final class MetricConnectorAggregationFilter extends GridAggregationFilterAbstract
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
            'status' => 'status',
        ];
    }

    /**
     * @return string[]
     */
    protected function orderCols(): array
    {
        return [
            'created' => 'fields.created',
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
        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('nodeId')
            ->expression('$tags.node_id')
            ->field('topologyId')
            ->expression('$tags.topology_id')
            ->field('applicationId')
            ->expression('$tags.application_id')
            ->field('created')
            ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$fields.created')
            ->field('status')
            ->expression('$fields.response_code')
            ->field('message')
            ->ifNull('$fields.response_error', NULL);
    }

}
