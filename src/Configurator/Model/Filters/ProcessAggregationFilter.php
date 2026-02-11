<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Closure;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Hanaboso\MongoDataGrid\GridAggregationFilterAbstract;
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
            'topologyId' => 'topologyId',
        ];
    }

    /**
     * @return string[]
     */
    protected function getSortations(): array
    {
        return [
            'created' => 'startedAt',
            'duration' => 'duration',
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
     * @return array<string, Closure(Builder): string[]>
     */
    protected function getSortationsCallbacks(): array
    {
        return [
            'duration' => static function (Builder $builder): array {
                $builder
                    ->addFields()
                    ->field('duration')
                    ->subtract(
                        $builder->expr()->ifNull('$finished', new UTCDateTime(DateTimeUtils::getUtcDateTime())),
                        '$created',
                    );

                return ['duration'];
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
            ->field('id')
            ->expression('$_id')
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
