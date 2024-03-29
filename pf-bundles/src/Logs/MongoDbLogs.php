<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\MongoDataGrid\GridRequestDto;
use Hanaboso\MongoDataGrid\Result\ResultData;
use Hanaboso\PipesFramework\Logs\Document\Logs;
use Hanaboso\Utils\Date\DateTimeUtils;

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
     * @param DocumentManager $dm
     * @param LogsFilter      $filter
     */
    public function __construct(private DocumentManager $dm, private LogsFilter $filter)
    {
        parent::__construct($dm);
    }

    /**
     * @param GridRequestDto $dto
     * @param int            $timeMargin
     *
     * @return mixed[]
     * @throws MongoDBException
     */
    public function getData(GridRequestDto $dto, int $timeMargin): array
    {
        $allLogs = [];

        if ($timeMargin > 0) {
            $allLogs = $this->getAllLogs();
        }

        $filteredLogs = $this->filter->getData($dto)->toArray();

        $result = $this->parseLogs($filteredLogs, $timeMargin, $allLogs);

        return GridFilterAbstract::getGridResponse($dto, $this->processStartingPoints($result));
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
        $result = [];

        foreach ($filteredLogs as $item) {
            $pipes    = $item[self::PIPES] ?? [];
            $result[] = $this->getResult($item, $pipes, $range, $allLogs);
        }

        return $result;
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
            self::CORRELATION_ID => $pipes[self::CORRELATIONID] ?? '',
            self::ID             => $id,
            self::MESSAGE        => $item[self::MESSAGE] ?? '',
            self::NODE_ID        => $pipes[self::NODEID] ?? '',
            self::NODE_NAME      => $pipes[self::NODENAME] ?? '',
            self::SERVICE        => $pipes[self::SERVICE] ?? '',
            self::SEVERITY       => $pipes[self::LEVEL] ?? '',
            self::TIMESTAMP      => DateTimeUtils::getUtcDateTimeFromTimeStamp($pipes[self::TIMESTAMP] ?? 0)
                ->format(DateTimeUtils::DATE_TIME_UTC),
            self::TOPOLOGY_ID    => $pipes[self::TOPOLOGYID] ?? '',
            self::TOPOLOGY_NAME  => $pipes[self::TOPOLOGYNAME] ?? '',
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
            'next' => $this->parseLogs(array_slice($allLogs[self::DATA], $index + 1, $max)),
            'prev' => $this->parseLogs(array_slice($allLogs[self::DATA], $min, $index - $min)),
        ];
    }

}
