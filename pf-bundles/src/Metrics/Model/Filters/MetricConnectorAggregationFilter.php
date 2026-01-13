<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
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
            'status' => 'status',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'created' => 'fields.created',
        ];
    }

    /**
     * @return array<string, Closure(Builder, mixed[], string, Expr, ?string): void>
     */
    protected function getConditionsCallbacks(): array
    {
        return [
            'status' => static function (
                Builder $builder,
                array $values,
                string $name,
                Expr $expr,
                ?string $operator,
            ): void {
                $builder;
                $operator;
                $name;

                match ($values[0]) {
                    'COMPLETED' => $expr->addAnd($builder->matchExpr()->field('fields.response_code')->lte(399)),
                    'FAILED' => $expr->addAnd($builder->matchExpr()->field('fields.response_code')->gte(400)),
                    default => throw new LogicException(
                        sprintf('Unknown status value `%s`.', $values[0]),
                        Response::HTTP_BAD_REQUEST,
                    ),
                };
            },
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
