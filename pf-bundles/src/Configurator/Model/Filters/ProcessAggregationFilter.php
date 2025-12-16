<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;
use LogicException;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ProcessAggregationFilter
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final class ProcessAggregationFilter extends GridAggregationFilterAbstract
{

    /**
     * @return string
     * @phpstan-return class-string
     */
    protected function getDocumentClass(): string
    {
        return TopologyProgress::class;
    }

    /**
     * @return string[]
     */
    protected function filterCols(): array
    {
        return [
            'created' => 'startedAt',
            'status' => 'status',
            'topologyId' => 'topologyId',
        ];
    }

    /**
     * @return string[]
     */
    protected function orderCols(): array
    {
        return [
            'created' => 'startedAt',
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
                        sprintf('Unknown status value `%s`.', $value[0]),
                        Response::HTTP_BAD_REQUEST,
                    ),
                };
            },
        ];
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
            ->lookup('Logs')
            ->let(['processId' => '$_id'])
            ->pipeline([
                [
                    '$match' => [
                        '$expr' => [
                            '$and' => [
                                ['$eq' => ['$pipes.correlation_id', '$$processId']],
                                ['$eq' => ['$pipes.severity', 'error']],
                            ],
                        ],
                    ],
                ],
                [
                    '$project' => [
                        'message' => 1,
                    ],
                ],
            ])
            ->alias('logs')
            ->project()
            ->field('topologyId')
            ->expression('$topologyId')
            ->field('created')
            ->dateToString('%Y-%m-%dT%H:%M:%SZ', '$created')
            ->field('status')
            ->cond(
                $builder->expr()->eq('$finished', NULL),
                'RUNNING',
                $builder->expr()->cond($builder->expr()->gt('$nok', 0), 'FAILED', 'COMPLETED'),
            )
            ->field('duration')
            ->subtract(
                $builder->expr()->ifNull('$finished', new UTCDateTime(DateTimeUtils::getUtcDateTime())),
                '$created',
            )
            ->field('messages')
            ->map('$logs', 'log', '$$log.message');
    }

}
