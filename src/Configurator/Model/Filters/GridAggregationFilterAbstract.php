<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Closure;
use DateTime;
use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Expr;
use Exception;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\MongoDataGrid\Result\ResultData;
use Hanaboso\Utils\Date\DateTimeUtils;
use LogicException;
use MongoDB\BSON\Regex;
use MongoDB\Driver\Exception\CommandException;

/**
 * Class GridAggregationFilterAbstract
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
abstract class GridAggregationFilterAbstract
{

    public const string EQ       = 'EQ';
    public const string NEQ      = 'NEQ';
    public const string IN       = 'IN';
    public const string NIN      = 'NIN';
    public const string GT       = 'GT';
    public const string LT       = 'LT';
    public const string GTE      = 'GTE';
    public const string LTE      = 'LTE';
    public const string LIKE     = 'LIKE';
    public const string STARTS   = 'STARTS';
    public const string ENDS     = 'ENDS';
    public const string NEMPTY   = 'NEMPTY';
    public const string EMPTY    = 'EMPTY';
    public const string BETWEEN  = 'BETWEEN';
    public const string NBETWEEN = 'NBETWEEN';
    public const string EXIST    = 'EXIST';
    public const string NEXIST   = 'NEXIST';

    public const string ASCENDING  = 'ASC';
    public const string DESCENDING = 'DESC';

    public const string COLUMN    = 'column';
    public const string OPERATOR  = 'operator';
    public const string VALUE     = 'value';
    public const string DIRECTION = 'direction';
    public const string SEARCH    = 'search';

    protected const string DATE_FORMAT = DateTimeUtils::DATE_TIME_UTC;

    /**
     * @var bool
     */
    protected bool $allowNative = FALSE;

    /**
     * @var mixed[]
     */
    protected array $projection = [];

    /**
     * @var string
     * @phpstan-var class-string
     */
    protected string $document;

    /**
     * @var bool
     */
    private bool $useTextSearch;

    /**
     * @var mixed[]
     */
    private array $orderCols;

    /**
     * @var mixed[]
     */
    private array $searchableCols;

    /**
     * @var mixed[]
     */
    private array $filterCols;

    /**
     * @var mixed[]
     */
    private array $filterColsCallbacks;

    /**
     * @return mixed[]
     */
    abstract protected function filterCols(): array;

    /**
     * @return mixed[]
     */
    abstract protected function orderCols(): array;

    /**
     * @return mixed[]
     */
    abstract protected function searchableCols(): array;

    /**
     * @return string
     * @phpstan-return class-string
     */
    abstract protected function getDocumentClass(): string;

    /**
     * @param Builder $builder
     * @param Closure $addConditionsCallback
     * @param Closure $addSortationsCallback
     * @param Closure $addPaginationCallback
     *
     * @return void
     */
    abstract protected function configureAggregationBuilder(
        Builder $builder,
        Closure $addConditionsCallback,
        Closure $addSortationsCallback,
        Closure $addPaginationCallback,
    ): void;


    /**
     * GridAggregationFilterAbstract constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(protected DocumentManager $dm)
    {
        $this->filterCols          = $this->filterCols();
        $this->orderCols           = $this->orderCols();
        $this->searchableCols      = $this->searchableCols();
        $this->filterColsCallbacks = $this->configFilterColsCallbacks();
        $this->useTextSearch       = $this->useTextSearch();
    }

    /**
     * @param GridRequestDtoInterface $gridRequestDto
     *
     * @return ResultData
     * @throws Exception
     */
    public function getData(GridRequestDtoInterface $gridRequestDto): ResultData
    {
        $builder = $this
            ->dm
            ->getRepository($this->getDocumentClass())
            ->createAggregationBuilder();

        $countBuilder = $this
            ->dm
            ->getRepository($this->getDocumentClass())
            ->createAggregationBuilder();

        $this->configureAggregationBuilder(
            $builder,
            function () use ($gridRequestDto, $builder): void {
                $this->processConditions($gridRequestDto, $builder);
            },
            function () use ($gridRequestDto, $builder): void {
                $this->processSortations($gridRequestDto, $builder);
            },
            function () use ($gridRequestDto, $builder): void {
                $this->processPagination($gridRequestDto, $builder);
            },
        );

        $this->configureCountAggregationBuilder(
            $countBuilder,
            function () use ($gridRequestDto, $countBuilder): void {
                $this->processConditions($gridRequestDto, $countBuilder);
            },
        );

        try {
            $data = $builder
                ->getAggregation()
                ->getIterator()
                ->toArray();

            $data = new ResultData($data, static::DATE_FORMAT);

            if ($this->useBetterCount()
                && $gridRequestDto->getFilter() === []
                && $gridRequestDto->getSearch() === NULL
            ) {
                $count = $this
                    ->dm
                    ->getRepository($this->getDocumentClass())
                    ->createQueryBuilder()
                    ->count()
                    ->getQuery()
                    ->execute();
            } else {
                // @phpstan-ignore-next-line
                $count = $countBuilder
                    ->count('count')
                    ->getAggregation()
                    ->getSingleResult()['count'] ?? 0;
            }

            $gridRequestDto->setTotal($count);
        } catch (CommandException $e) {
            if ($e->getCode() === 27) {
                throw new LogicException(
                    sprintf(
                        "Column cannot be used for searching! Missing TEXT index on '%s::searchableCols' fields!",
                        static::class,
                    ),
                );
            }

            throw $e;
        }

        return $data;
    }

    /**
     * @param Builder     $builder
     * @param string      $name
     * @param mixed       $value
     * @param string|NULL $operator
     *
     * @return Expr
     */
    public static function getCondition(Builder $builder, string $name, mixed $value, ?string $operator = NULL): Expr
    {
        switch ($operator) {
            case self::EQ:
                return is_array($value)
                    ? $builder->matchExpr()->field($name)->in($value)
                    : $builder->matchExpr()->field($name)->equals($value);
            case self::NEQ:
                return is_array($value)
                    ? $builder->matchExpr()->field($name)->notIn($value)
                    : $builder->matchExpr()->field($name)->notEqual($value);
            case self::IN:
                return $builder->matchExpr()->field($name)->in($value);
            case self::NIN:
                return $builder->matchExpr()->field($name)->notIn($value);
            case self::GTE:
                return $builder->matchExpr()->field($name)->gte(self::getValue($value));
            case self::GT:
                return $builder->matchExpr()->field($name)->gt(self::getValue($value));
            case self::LTE:
                return $builder->matchExpr()->field($name)->lte(self::getValue($value));
            case self::LT:
                return $builder->matchExpr()->field($name)->lt(self::getValue($value));
            case self::NEMPTY:
                return $builder->matchExpr()
                    ->addOr($builder->matchExpr()->field($name)->notEqual(NULL))
                    ->addOr($builder->matchExpr()->field($name)->notEqual(self::getValue($value)));
            case self::EMPTY:
                return $builder->matchExpr()
                    ->addOr($builder->matchExpr()->field($name)->equals(NULL))
                    ->addOr($builder->matchExpr()->field($name)->equals(self::getValue($value)));
            case self::LIKE:
                return $builder->matchExpr()->field($name)->equals(
                    new Regex(sprintf('%s', preg_quote(self::getValue($value))), 'i'),
                );
            case self::STARTS:
                return $builder->matchExpr()->field($name)->equals(
                    new Regex(sprintf('^%s', preg_quote(self::getValue($value))), 'i'),
                );
            case self::ENDS:
                return $builder->matchExpr()->field($name)->equals(
                    new Regex(sprintf('%s$', preg_quote(self::getValue($value))), 'i'),
                );
            case self::BETWEEN:
                if (is_array($value) && count($value) >= 2) {
                    return $builder->matchExpr()
                        ->addAnd($builder->matchExpr()->field($name)->gte($value[0]))
                        ->addAnd($builder->matchExpr()->field($name)->lte($value[1]));
                }

                return $builder->matchExpr()->field($name)->equals(self::getValue($value));
            case self::NBETWEEN:
                if (is_array($value) && count($value) >= 2) {
                    return $builder->matchExpr()
                        ->addOr($builder->matchExpr()->field($name)->lte($value[0]))
                        ->addOr($builder->matchExpr()->field($name)->gte($value[1]));
                }

                return $builder->matchExpr()->field($name)->notEqual(self::getValue($value));
            case self::EXIST:
                return  $builder->matchExpr()->field($name)->exists(TRUE);
            case self::NEXIST:
                return  $builder->matchExpr()->field($name)->exists(FALSE);
            default:
                return $builder->matchExpr()->field($name)->equals(self::getValue($value));
        }
    }

    /**
     * @param GridRequestDtoInterface $dto
     * @param mixed[]                 $items
     *
     * @return mixed[]
     */
    public static function getGridResponse(GridRequestDtoInterface $dto, array $items): array
    {
        $total    = $dto->getTotal();
        $page     = $dto->getPage();
        $lastPage = (int) max(1, ceil($dto->getTotal() / $dto->getItemsPerPage()));

        return [
            'filter' => $dto->getFilter(FALSE),
            'items'  => $items,
            'paging' => [
                'itemsPerPage' => $dto->getItemsPerPage(),
                'lastPage'     => $lastPage,
                'nextPage'     => min($lastPage, $page + 1),
                'page'         => $page,
                'previousPage' => max(1, $page - 1),
                'total'        => $total,
            ],
            'search' => $dto->getSearch(),
            'sorter' => $dto->getOrderBy(),
        ];
    }

    /**
     * @return bool
     */
    protected function useTextSearch(): bool
    {
        return FALSE;
    }

    /**
     * @return bool
     */
    protected function useBetterCount(): bool
    {
        return TRUE;
    }

    /**
     * In child can configure GridFilterAbstract::filterColsCallbacks
     * example child content
     *
     * return [ESomeEnumCols::CREATED_AT_FROM => function (Builder $builder,string $value,string $name,Expr $expr,?string $operator){}]
     *
     * @return mixed[]
     */
    protected function configFilterColsCallbacks(): array
    {
        return [];
    }

    /**
     * @param Builder $builder
     * @param Closure $addConditionsCallback
     *
     * @return void
     */
    protected function configureCountAggregationBuilder(Builder $builder, Closure $addConditionsCallback): void {
        $builder;

        $addConditionsCallback();
    }

    /**
     * -------------------------------------------- HELPERS -----------------------------------------------
     */

    /**
     * @param GridRequestDtoInterface $dto
     * @param Builder                 $builder
     *
     * @throws GridException
     */
    private function processSortations(GridRequestDtoInterface $dto, Builder $builder): void
    {
        $sortations = $this->parseSortations($dto);

        if ($sortations === []) {
            return;
        }

        $innerSortations = [];

        foreach ($sortations as $column => $direction) {
            $innerSortations[$this->orderCols[$column]] = $direction;
        }

        $builder->sort($innerSortations);
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return mixed[]
     * @throws GridException
     */
    private function parseSortations(GridRequestDtoInterface $dto): array
    {
        $sortations = $dto->getOrderBy();
        $toSort     = [];

        if ($sortations) {
            foreach ($sortations as $sortation) {
                $column    = $sortation[self::COLUMN];
                $direction = $sortation[self::DIRECTION];
                if (!isset($this->orderCols[$column])) {
                    throw new GridException(
                        sprintf(
                            "Column '%s' cannot be used for sorting! Have you forgotten add it to '%s::orderCols'?",
                            $column,
                            static::class,
                        ),
                        GridException::SORT_COLS_ERROR,
                    );
                }

                $toSort[$column] = $direction;
            }
        }

        return $toSort;
    }

    /**
     * @param GridRequestDtoInterface $dto
     * @param Builder                 $builder
     *
     * @throws Exception
     */
    private function processConditions(GridRequestDtoInterface $dto, Builder $builder): void
    {
        $conditions          = $dto->getFilter();
        $conditionExpression = $builder->matchExpr();

        $exp = FALSE;
        foreach ($conditions as $andCondition) {
            $hasExpression = FALSE;
            $expression    = $builder->matchExpr();

            foreach ($andCondition as $orCondition) {
                if (!array_key_exists(self::COLUMN, $orCondition) ||
                    !array_key_exists(self::OPERATOR, $orCondition) ||
                    !array_key_exists(self::VALUE, $orCondition) &&
                    !in_array(
                        $orCondition[self::OPERATOR],
                        [self::EMPTY, self::NEMPTY, self::EXIST, self::NEXIST],
                        TRUE,
                    )) {
                    throw new LogicException(
                        sprintf(
                            "Advanced filter must have '%s', '%s' and '%s' field!",
                            self::COLUMN,
                            self::OPERATOR,
                            self::VALUE,
                        ),
                    );
                }

                if (!array_key_exists(self::VALUE, $orCondition)) {
                    $orCondition[self::VALUE] = '';
                }

                $column = $orCondition[self::COLUMN];

                $this->checkFilterColumn($column);
                $hasExpression            = TRUE;
                $orCondition[self::VALUE] = $this->processDateTime($orCondition[self::VALUE]);

                if (isset($this->filterColsCallbacks[$column])) {
                    $expr = $builder->matchExpr();

                    $this->filterColsCallbacks[$column](
                        $builder,
                        $orCondition[self::VALUE],
                        $this->filterCols[$column],
                        $expr,
                        $orCondition[self::OPERATOR]
                    );
                    $expression->addOr($expr);

                    continue;
                }

                $expression->addOr(
                    self::getCondition(
                        $builder,
                        $this->filterCols[$column],
                        $orCondition[self::VALUE],
                        $orCondition[self::OPERATOR],
                    ),
                );
            }

            if ($hasExpression) {
                $conditionExpression->addAnd($expression);
                $exp = TRUE;
            }
        }

        if ($exp) {
            $builder->match()->addAnd($conditionExpression);
        }

        $search = $dto->getSearch();

        if ($search) {
            if ($this->useTextSearch) {
                $builder->match()->text($search);
            }

            $searchExpression = $builder->matchExpr();

            if (!$this->searchableCols) {
                throw new GridException(
                    sprintf(
                        "Column cannot be used for searching! Have you forgotten add it to '%s::searchableCols'?",
                        static::class,
                    ),
                    GridException::SEARCHABLE_COLS_ERROR,
                );
            }

            foreach ($this->searchableCols as $column) {
                if (!array_key_exists($column, $this->filterCols)) {
                    throw new GridException(
                        sprintf(
                            "Column '%s' cannot be used for searching! Have you forgotten add it to '%s::filterCols'?",
                            $column,
                            static::class,
                        ),
                        GridException::SEARCHABLE_COLS_ERROR,
                    );
                }

                if (isset($this->filterColsCallbacks[$column])) {
                    $expression = $builder->matchExpr();

                    $this->filterColsCallbacks[$column](
                        $builder,
                        $search,
                        $this->filterCols[$column],
                        $expression,
                        NULL
                    );

                    $searchExpression->addOr($expression);

                    continue;
                }

                $searchExpression->addOr(self::getCondition($builder, $this->filterCols[$column], $search, self::LIKE));
            }

            $builder->match()->addAnd($searchExpression);
        }
    }

    /**
     * @param GridRequestDtoInterface $dto
     * @param Builder                 $builder
     */
    private function processPagination(GridRequestDtoInterface $dto, Builder $builder): void
    {
        $page  = $dto->getPage();
        $limit = $dto->getItemsPerPage();

        $builder->skip(--$page * $limit)->limit($limit);
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     * @throws Exception
     */
    private function processDateTime(mixed $value): mixed
    {
        $values = $value;
        if (!is_array($value)) {
            $values = [$value];
        }

        foreach ($values as $index => $val) {
            if (is_string($val) && preg_match('/\d{4}-\d{2}-\d{2}.\d{2}:\d{2}:\d{2}/', $val)) {
                $values[$index] = new DateTime($val);
            }
        }

        return $values;
    }

    /**
     * @param string $column
     *
     * @throws GridException
     */
    private function checkFilterColumn(string $column): void
    {
        if (!isset($this->filterCols[$column])) {
            throw new GridException(
                sprintf(
                    "Column '%s' cannot be used for filtering! Have you forgotten add it to '%s::filterCols'?",
                    $column,
                    static::class,
                ),
                GridException::FILTER_COLS_ERROR,
            );
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    private static function getValue(mixed $value): mixed
    {
        return is_array($value) ? $value[0] : $value;
    }

}
