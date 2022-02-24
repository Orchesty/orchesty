<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\MongoDataGrid\Result\ResultData;
use Hanaboso\PipesFramework\Logs\Document\Logs;
use Throwable;

/**
 * Class MongoDbLogs
 *
 * @package Hanaboso\PipesFramework\Logs
 */
final class MongoDbLogs extends LogsAbstract
{

    private const RELATED_LOGS = 'related_logs';
    private const DATA         = 'data';
    private const MAP          = 'map';

    /**
     * MongoDbLogs constructor.
     *
     * @param DocumentManager      $dm
     * @param LogsFilter           $filter
     * @param StartingPointsFilter $startingPointsFilter
     */
    public function __construct(
        private DocumentManager $dm,
        private LogsFilter $filter,
        StartingPointsFilter $startingPointsFilter,
    )
    {
        parent::__construct($dm, $startingPointsFilter);
    }

    /**
     * @param GridRequestDto $dto
     *
     * @return mixed[]
     * @throws MongoDBException
     * @throws Exception
     */
    public function getData(GridRequestDto $dto): array
    {
        $range   = NULL;
        $column  = FALSE;
        $allLogs = [];
        $filters = [];

        try {
            $filters = $dto->getFilter()[0];
            $column  = array_search(
                'time_margin',
                array_column($filters ?? [], GridFilterAbstract::COLUMN),
                TRUE,
            );
        } catch (Throwable) {
            $range = NULL;
        }

        if ($column !== FALSE) {
            $range   = $filters[$column][GridFilterAbstract::VALUE];
            $allLogs = $this->getAllLogs();
        }

        $filteredLogs = $this->filter->getData($dto)->toArray();

        [$result, $correlationIds] = $this->parseLogs($filteredLogs, $range, $allLogs);

        $innerDto = new GridRequestDto(['limit' => self::LIMIT]);
        if ($correlationIds) {
            $innerDto->setAdditionalFilters(
                [
                    [
                        [
                            GridFilterAbstract::COLUMN   => self::CORRELATIONID,
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::EQ,
                            GridFilterAbstract::VALUE    => $correlationIds,
                        ],
                    ],
                    [
                        [
                            GridFilterAbstract::COLUMN   => self::TOPOLOGYID,
                            GridFilterAbstract::OPERATOR => GridFilterAbstract::NEMPTY,
                        ],
                    ],
                ],
            );
        }

        return GridFilterAbstract::getGridResponse($dto, $this->processStartingPoints($innerDto, $result));
    }

    /**
     * @return mixed[]
     * @throws MongoDBException
     */
    private function getAllLogs(): array
    {
        $allLogs    = (new ResultData(
            $this->dm->createQueryBuilder(Logs::class)
                ->sort(Logs::MONGO_ID)
                ->hydrate(FALSE)
                ->getQuery()
                ->toArray(),
        ))->toArray();
        $allLogsMap = array_flip(array_column($allLogs, 'id'));

        return [self::MAP => $allLogsMap, self::DATA => $allLogs];
    }

    /**
     * @param mixed[]      $filteredLogs
     * @param int|null     $range
     * @param mixed[]|null $allLogs
     *
     * @return mixed[]
     */
    private function parseLogs(array $filteredLogs, ?int $range = NULL, ?array $allLogs = NULL): array
    {
        $result         = [];
        $correlationIds = [];

        foreach ($filteredLogs as $item) {
            $pipes         = $item[self::PIPES] ?? [];
            $result[]      = $this->getResult($item, $pipes, $range, $allLogs);
            $correlationId = $this->getNonEmptyValue($pipes, self::CORRELATION_ID);
            if ($correlationId) {
                $correlationIds[] = $correlationId;
            }
        }

        return [$result, $correlationIds];
    }

    /**
     * @param mixed[]      $item
     * @param mixed[]      $pipes
     * @param int|null     $range
     * @param mixed[]|null $allLogs
     *
     * @return mixed[]
     */
    private function getResult(array $item, array $pipes, ?int $range, ?array $allLogs): array
    {
        $id     = array_key_exists(self::ID, $item) ? (string) $item[self::ID] : '';
        $result = [
            self::ID             => $id,
            self::SEVERITY       => $pipes[self::LEVEL] ?? '',
            self::MESSAGE        => $item[self::MESSAGE] ?? '',
            self::TYPE           => $pipes[self::TYPE] ?? '',
            self::CORRELATION_ID => $pipes[self::CORRELATIONID] ?? '',
            self::TOPOLOGY_ID    => $pipes[self::TOPOLOGYID] ?? '',
            self::TOPOLOGY_NAME  => $pipes[self::TOPOLOGYNAME] ?? '',
            self::NODE_ID        => $pipes[self::NODEID] ?? '',
            self::NODE_NAME      => $pipes[self::NODENAME] ?? '',
            self::TIMESTAMP      => str_replace('"', '', $item['ts'] ?? $item[self::TIMESTAMP_PREFIX] ?? ''),
        ];
        if ($range && $id && $allLogs) {
            return array_merge($result, [self::RELATED_LOGS => $this->getPrevNext($id, $range, $allLogs)]);
        }

        return $result;
    }

    /**
     * @param string  $filteredLogId
     * @param int     $range
     * @param mixed[] $allLogs
     *
     * @return mixed[]
     */
    private function getPrevNext(string $filteredLogId, int $range, array $allLogs): array
    {
        $total = count($allLogs[self::DATA]) - 1;
        $index = $allLogs[self::MAP][$filteredLogId];

        $min = $index - $range < 0 ? 0 : $index - $range;
        $max = $range > $total ? $total : $range;

        return [
            'prev' => $this->parseLogs(array_slice($allLogs[self::DATA], $min, $index - $min))[0],
            'next' => $this->parseLogs(array_slice($allLogs[self::DATA], $index + 1, $max))[0],
        ];
    }

}
