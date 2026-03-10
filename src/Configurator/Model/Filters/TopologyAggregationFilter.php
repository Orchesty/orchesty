<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use LogicException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TopologyAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final class TopologyAggregationFilter extends GridAggregationFilterAbstract
{

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
            'status' => 'status',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'count' => 'count',
            'created' => 'created',
            'failedCount' => 'failedCount',
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
                    'RUNNING' => $expr->addAnd($builder->matchExpr()->field('finished')->equals(NULL)),
                    'COMPLETED' => $expr->addAnd(
                        $builder->matchExpr()->field('finished')->notEqual(NULL),
                        $builder->matchExpr()->field('nok')->equals(0),
                    ),
                    'FAILED' => $expr->addAnd(
                        $builder->matchExpr()->field('finished')->notEqual(NULL),
                        $builder->matchExpr()->field('nok')->notEqual(0),
                    ),
                    default => throw new LogicException(
                        sprintf('Unknown status value `%s`.', $values[0]),
                        Response::HTTP_BAD_REQUEST,
                    ),
                };
            },
        ];
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

        $builder
            ->sort(['startedAt' => 'desc'])
            ->group()
            ->field('_id')
            ->expression('$topologyId')
            ->field('topologyId')
            ->first('$topologyId')
            ->field('created')
            ->first('$startedAt')
            ->field('count')
            ->sum(1)
            ->field('failedCount')
            ->sum(
                $builder->expr()->cond(
                    $builder->expr()->gt('$nok', 0),
                    1,
                    0,
                ),
            )
            ->field('finished')
            ->first('$finished')
            ->field('nok')
            ->first('$nok');

        $addSortationsCallback();
        $addPaginationCallback();

        $builder
            ->project()
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('created')
            ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$created')
            ->field('count')
            ->expression('$count')
            ->field('failedCount')
            ->expression('$failedCount')
            ->field('status')
            ->cond(
                $builder->expr()->eq('$finished', NULL),
                'RUNNING',
                $builder->expr()->cond($builder->expr()->gt('$nok', 0), 'FAILED', 'COMPLETED'),
            );
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
            ->expression('$topologyId');
    }

}
