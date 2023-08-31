<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\DocumentManager;
use Elastica\Client;
use Elastica\Exception\ResponseException;
use Elastica\Request;
use Hanaboso\MongoDataGrid\Exception\GridException;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;

/**
 * Class ElasticLogs
 *
 * @package Hanaboso\PipesFramework\Logs
 */
final class ElasticLogs extends LogsAbstract
{

    private const SOURCE = '_source';
    private const HITS   = 'hits';
    private const TERMS  = 'terms';
    private const TERM   = 'term';
    private const QUERY  = 'query';
    private const BOOL   = 'bool';
    private const MUST   = 'must';
    private const SORT   = 'sort';
    private const ORDER  = 'order';
    private const ASC    = 'asc';
    private const SIZE   = 'size';
    private const FROM   = 'from';
    private const COUNT  = 'count';

    private const PIPES_SEVERITY_KEYWORD = 'pipes.severity.keyword';

    private const CONVERT = [
        self::TIMESTAMP     => self::TIMESTAMP_PREFIX,
        self::SEVERITY      => self::PIPES_SEVERITY_KEYWORD,
        self::TOPOLOGY_ID   => 'pipes.topology_id.keyword',
        self::TOPOLOGY_NAME => 'pipes.topology_name.keyword',
        self::NODE_ID       => 'pipes.node_id.keyword',
        self::NODE_NAME     => 'pipes.node_name.keyword',
        self::MESSAGE       => 'message.keyword',
    ];

    private const EXCEPTION        = 'in order to sort on';
    private const COUNT_QUERY      = '%s*/_count';
    private const SEARCH_QUERY     = '%s*/_search';
    private const DEFAULT_SORTER   = [self::SORT => [self::TIMESTAMP_PREFIX => [self::ORDER => self::ASC]]];
    private const DEFAULT_SEVERITY = [
        'alert',
        'warning',
        'error',
        'critical',
        'ALERT',
        'WARNING',
        'ERROR',
        'CRITICAL',
    ];

    /**
     * @var string
     */
    private string $index;

    /**
     * ElasticLogs constructor.
     *
     * @param DocumentManager $dm
     * @param Client          $client
     */
    public function __construct(DocumentManager $dm, private Client $client)
    {
        parent::__construct($dm);
    }

    /**
     * @param string $index
     *
     * @return ElasticLogs
     */
    public function setIndex(string $index): ElasticLogs
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @param GridRequestDto $dto
     * @param int            $timeMargin
     *
     * @return mixed[]
     */
    public function getData(GridRequestDto $dto, int $timeMargin): array
    {
        $timeMargin;
        [$filter, $sorter] = $this->getFilterAndSorter($dto);

        try {
            $data = $this->getInnerData($filter, $sorter, $dto->getPage(), $dto->getItemsPerPage());
        } catch (ResponseException $e) { // Intentionally, because some fields can be missing in ElasticSearch...
            if (!str_contains($e->getMessage(), self::EXCEPTION)) {
                throw $e;
            }

            $data = $this->getInnerData($filter, self::DEFAULT_SORTER, $dto->getPage(), $dto->getItemsPerPage());
        }

        $result = [];

        foreach ($data[self::HITS][self::HITS] ?? [] as $item) {
            $pipes = $item[self::SOURCE][self::PIPES];

            $result[] = [
                self::ID             => $item[self::_ID] ?? '',
                self::SEVERITY       => $pipes[self::SEVERITY] ?? '',
                self::MESSAGE        => $item[self::SOURCE][self::MESSAGE] ?? '',
                self::TYPE           => $pipes[self::TYPE] ?? '',
                self::CORRELATION_ID => $pipes[self::CORRELATION_ID] ?? '',
                self::TOPOLOGY_ID    => $pipes[self::TOPOLOGY_ID] ?? '',
                self::TOPOLOGY_NAME  => $pipes[self::TOPOLOGY_NAME] ?? '',
                self::NODE_ID        => $pipes[self::NODE_ID] ?? '',
                self::NODE_NAME      => $pipes[self::NODE_NAME] ?? '',
                self::TIMESTAMP      => $item[self::SOURCE][self::TIMESTAMP_PREFIX] ?? '',
            ];
        }

        $page     = $dto->getPage();
        $lastPage = (int) max(1, ceil($dto->getTotal() / $dto->getItemsPerPage()));

        return [
            'items'  => $this->processStartingPoints($result),
            'filter' => $dto->getFilter(FALSE),
            'sorter' => $dto->getOrderBy(),
            'search' => $dto->getSearch(),
            'paging' => [
                'page'         => $page,
                'itemsPerPage' => $dto->getItemsPerPage(),
                'total'        => $this->getInnerCount($filter),
                'nextPage'     => min($lastPage, $page + 1),
                'lastPage'     => $lastPage,
                'previousPage' => max(1, $page - 1),
            ],
        ];
    }

    /**
     * @param mixed[] $filter
     * @param mixed[] $sorter
     * @param int     $page
     * @param int     $limit
     *
     * @return mixed[]
     */
    private function getInnerData(array $filter, array $sorter, int $page, int $limit): array
    {
        return $this->client->request(
            sprintf(self::SEARCH_QUERY, $this->index),
            Request::GET,
            array_merge(
                [
                    self::SIZE => $limit,
                    self::FROM => ($page - 1) * $page,
                ],
                $filter,
                $sorter,
            ),
        )->getData();
    }

    /**
     * @param mixed[] $filter
     *
     * @return int
     */
    private function getInnerCount(array $filter): int
    {
        return $this->client
                   ->request(sprintf(self::COUNT_QUERY, $this->index), Request::GET, $filter)
                   ->getData()[self::COUNT];
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     * @throws GridException
     */
    private function getFilterAndSorter(GridRequestDto $dto): array
    {
        $search = $dto->getSearch();
        $filter = $dto->getFilter();
        $sorter = $dto->getOrderBy();

        $innerFilter = [];
        $innerSorter = [];

        if (isset($filter[0][0][GridFilterAbstract::COLUMN]) &&
            $filter[0][0][GridFilterAbstract::COLUMN] === self::SEVERITY) {
            $innerFilter[] = [
                self::TERMS => [
                    self::PIPES_SEVERITY_KEYWORD => [$filter[0][0][GridFilterAbstract::VALUE]],
                ],
            ];
        } else {
            $innerFilter[] = [
                self::TERMS => [
                    self::PIPES_SEVERITY_KEYWORD => self::DEFAULT_SEVERITY,
                ],
            ];
        }

        if ($search) {
            $innerFilter[] = [self::TERM => [self::MESSAGE => $search]];
        }

        $innerFilter = [self::QUERY => [self::BOOL => [self::MUST => $innerFilter]]];

        foreach ($sorter as $sort) {
            $innerSorter[self::CONVERT[$sort[GridFilterAbstract::COLUMN]]] = [
                self::ORDER => strtolower($sort[GridFilterAbstract::DIRECTION]),
            ];
        }

        return [$innerFilter, $innerSorter ? [self::SORT => $innerSorter] : []];
    }

}