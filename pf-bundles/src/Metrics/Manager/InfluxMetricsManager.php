<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Manager;

use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Metrics\Client\ClientInterface;
use Hanaboso\PipesFramework\Metrics\Dto\MetricsDto;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\Retention\RetentionFactory;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use Hanaboso\Utils\Date\DateTimeUtils;
use Hanaboso\Utils\Exception\DateTimeException;
use Hanaboso\Utils\System\NodeGeneratorUtils;
use Throwable;

/**
 * Class InfluxMetricsManager
 *
 * @package Hanaboso\PipesFramework\Metrics\Manager
 */
final class InfluxMetricsManager extends MetricsManagerAbstract
{

    /**
     * @var ClientInterface
     */
    private ClientInterface $client;

    /**
     * InfluxMetricsManager constructor.
     *
     * @param ClientInterface $client
     * @param DocumentManager $dm
     * @param string          $nodeTable
     * @param string          $fpmTable
     * @param string          $rabbitTable
     * @param string          $counterTable
     * @param string          $connectorTable
     */
    public function __construct(
        ClientInterface $client,
        DocumentManager $dm,
        string $nodeTable,
        string $fpmTable,
        string $rabbitTable,
        string $counterTable,
        string $connectorTable
    )
    {
        parent::__construct($dm, $nodeTable, $fpmTable, $rabbitTable, $counterTable, $connectorTable);

        $this->client = $client;
    }

    /**
     * @param Node     $node
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     * @throws MetricsException
     * @throws DateTimeException
     */
    public function getNodeMetrics(Node $node, Topology $topology, array $params): array
    {
        $dateFrom = $params['from'] ?? NULL;
        $dateTo   = $params['to'] ?? NULL;
        $from     = sprintf(
            '%s,%s,%s,%s',
            $this->nodeTable,
            $this->fpmTable,
            $this->rabbitTable,
            $this->connectorTable
        );

        $select = self::addStringSeparator(
            self::getFunctionForSelect(
                [
                    self::AVG_PROCESS_TIME => self::PROCESSED_COUNT,
                    self::AVG_WAIT_TIME    => self::WAIT_COUNT,
                    self::CPU_KERNEL_AVG   => self::CPU_COUNT,
                    self::AVG_TIME         => self::REQUEST_COUNT,
                    self::AVG_MESSAGES     => self::QUEUE_COUNT,
                ],
                self::COUNT
            )
        );

        $select .= self::addStringSeparator(
            self::getFunctionForSelect(
                [
                    self::AVG_PROCESS_TIME => self::PROCESSED_SUM,
                    self::AVG_WAIT_TIME    => self::WAIT_SUM,
                    self::CPU_KERNEL_AVG   => self::CPU_SUM,
                    self::AVG_TIME         => self::REQUEST_SUM,
                    self::FAILED_COUNT     => self::NODE_ERROR_SUM,
                    self::TOTAL_COUNT      => self::NODE_TOTAL_SUM,
                    self::AVG_MESSAGES     => self::QUEUE_SUM,
                ],
                self::SUM
            )
        );

        $select .= self::addStringSeparator(
            self::getFunctionForSelect(
                [
                    self::MIN_PROCESS_TIME => self::PROCESSED_MIN,
                    self::MIN_WAIT_TIME    => self::WAIT_MIN,
                    self::CPU_KERNEL_MIN   => self::CPU_MIN,
                    self::MIN_TIME         => self::REQUEST_MIN,
                ],
                self::MIN
            )
        );

        $select .= self::getFunctionForSelect(
            [
                self::MAX_PROCESS_TIME => self::PROCESSED_MAX,
                self::MAX_WAIT_TIME    => self::WAIT_MAX,
                self::CPU_KERNEL_MAX   => self::CPU_MAX,
                self::MAX_TIME         => self::REQUEST_MAX,
                self::MAX_MESSAGES     => self::QUEUE_MAX,
            ],
            self::MAX
        );

        $where = [
            self::NODE  => $node->getId(),
            self::QUEUE => NodeGeneratorUtils::generateQueueName($topology->getId(), $node->getId(), $node->getName()),
        ];

        return $this->runQuery($select, $from, $where, NULL, $dateFrom, $dateTo);
    }

    /**
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     * @throws MetricsException
     * @throws DateTimeException
     */
    public function getTopologyProcessTimeMetrics(Topology $topology, array $params): array
    {
        $dateFrom = $params['from'] ?? NULL;
        $dateTo   = $params['to'] ?? NULL;
        $from     = $this->counterTable;

        $select  = self::getFunctionForSelect([self::AVG_TIME => self::PROCESS_TIME_COUNT], self::COUNT);
        $select  = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::AVG_TIME => self::PROCESS_TIME_SUM], self::SUM);
        $select  = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::MIN_TIME => self::PROCESS_TIME_MIN], self::MIN);
        $select  = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::MAX_TIME => self::PROCESS_TIME_MAX], self::MAX);
        $select  = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::TOTAL_COUNT => self::NODE_TOTAL_SUM], self::SUM);
        $select  = self::addStringSeparator($select);
        $select .= self::getFunctionForSelect([self::FAILED_COUNT => self::NODE_ERROR_SUM], self::SUM);

        $where = [self::TOPOLOGY => $topology->getId()];

        return $this->runQuery($select, $from, $where, NULL, $dateFrom, $dateTo);
    }

    /**
     * @param Topology $topology
     * @param mixed[]  $params
     *
     * @return mixed[]
     * @throws MetricsException
     * @throws DateTimeException
     */
    public function getTopologyRequestCountMetrics(Topology $topology, array $params): array
    {
        $data = $this->getTopologyMetrics($topology, $params);

        $dateFrom = $params['from'] ?? 'now -1h';
        $dateTo   = $params['to'] ?? 'now';
        $groupBy  = sprintf(
            'TIME(%s)',
            RetentionFactory::getRetention(
                DateTimeUtils::getUtcDateTime($dateFrom),
                DateTimeUtils::getUtcDateTime($dateTo)
            )
        );

        $data['requests'] = $this->runQuery(
            sprintf('SUM(%s) AS count', self::TOTAL_COUNT),
            $this->counterTable,
            [sprintf("%s = '%s'", self::TOPOLOGY, $topology->getId())],
            $groupBy,
            $dateFrom,
            $dateTo,
            TRUE
        );

        return $data;
    }

    /**
     * @param mixed[] $series
     *
     * @return mixed[] $points
     */
    public function getPoints(array $series): array
    {
        $points = [];

        foreach ($series as $serie) {
            if (isset($serie['values']) && isset($serie['name'])) {
                foreach ($this->getPointsFromSerie($serie) as $point) {
                    $points[$serie['name']] = $point;
                }
            }
        }

        return $points;
    }

    /**
     * -------------------------------------------- HELPERS ---------------------------------------------
     */

    /**
     * @param string      $select
     * @param string      $from
     * @param mixed[]     $where
     * @param string|null $group
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param bool        $forGraph
     *
     * @return mixed[]
     * @throws MetricsException
     * @throws DateTimeException
     */
    private function runQuery(
        string $select,
        string $from,
        array $where,
        ?string $group = NULL,
        ?string $dateFrom = NULL,
        ?string $dateTo = NULL,
        bool $forGraph = FALSE
    ): array
    {
        $qb = $this->client->getQueryBuilder()
            ->select($select)
            ->from($from)
            ->where($forGraph ? $where : self::getConditions($where));

        if ($group) {
            $qb->groupBy($group);
        }

        if ($dateFrom && $dateTo) {
            $dateFrom = DateTimeUtils::getUtcDateTime($dateFrom);
            $dateTo   = DateTimeUtils::getUtcDateTime($dateTo);
            $qb
                ->setTimeRange($dateFrom->getTimestamp(), $dateTo->getTimestamp())
                ->from($this->addRetentionPolicy($from, $dateFrom, $dateTo));
        }
        $this->logger->debug('Metrics was selected.', ['Query' => $qb->getQuery()]);
        try {
            $series = $qb->getResultSet()->getSeries();
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), ['Exception' => $e]);

            throw new MetricsException('Unknown error occurred during query.', MetricsException::QUERY_ERROR);
        }

        if ($forGraph) {
            return $this->processGraphResult($series);
        }

        return $this->processResultSet($this->getPoints($series));
    }

    /**
     * @param mixed[] $serie
     *
     * @return mixed[]
     */
    private function getPointsFromSerie(array $serie): array
    {
        $points = [];

        foreach ($serie['values'] as $point) {
            $points[] = array_combine($serie['columns'], $point);
        }

        return $points;
    }

    /**
     * @param MetricsDto $dto
     * @param mixed[]    $result
     * @param string     $type
     *
     * @return void
     */
    private function processInnerResult(MetricsDto $dto, array $result, string $type): void
    {
        $dto
            ->setMin($result[sprintf($type, self::MIN_KEY)] ?? '')
            ->setAvg($result[sprintf($type, self::COUNT_KEY)] ?? '', $result[sprintf($type, self::SUM_KEY)] ?? '')
            ->setMax($result[sprintf($type, self::MAX_KEY)] ?? '');
    }

    /**
     * @param mixed[] $result
     *
     * @return mixed[]
     */
    private function processResultSet(array $result): array
    {
        $waiting = new MetricsDto();
        $process = new MetricsDto();
        $cpu     = new MetricsDto();
        $request = new MetricsDto();
        $queue   = new MetricsDto();
        $error   = new MetricsDto();
        $counter = new MetricsDto();

        if (isset($result[$this->fpmTable])) {
            $this->processInnerResult($cpu, $result[$this->fpmTable], self::CPU_KEY);
        }

        if (isset($result[$this->connectorTable])) {
            $this->processInnerResult($request, $result[$this->connectorTable], self::REQUEST_KEY);
        }

        if (isset($result[$this->nodeTable])) {
            $this->processInnerResult($waiting, $result[$this->nodeTable], self::WAIT_KEY);
            $this->processInnerResult($process, $result[$this->nodeTable], self::PROCESSED_KEY);
            $error
                ->setTotal($result[$this->nodeTable][self::NODE_TOTAL_SUM] ?? '')
                ->setErrors($result[$this->nodeTable][self::NODE_ERROR_SUM] ?? '');
        }

        if (isset($result[$this->rabbitTable])) {
            $this->processInnerResult($queue, $result[$this->rabbitTable], self::QUEUE_KEY);
        }

        if (isset($result[$this->counterTable])) {
            $this->processInnerResult($counter, $result[$this->counterTable], self::PROCESS_TIME_KEY);
            $error
                ->setTotal($result[$this->counterTable][self::NODE_TOTAL_SUM] ?? '')
                ->setErrors($result[$this->counterTable][self::NODE_ERROR_SUM] ?? '');
        }

        return $this->generateOutput($queue, $waiting, $process, $cpu, $request, $error, $counter);
    }

    /**
     * @param mixed[] $series
     *
     * @return mixed[]
     * @throws DateTimeException
     */
    private function processGraphResult(array $series): array
    {
        $data = [];

        if (isset($series[0]['values'])) {
            for ($i = 0; $i < count($series[0]['values']) - 4; $i++) {
                $item                                                          = $series[0]['values'][$i];
                $data[DateTimeUtils::getUtcDateTime($item[0])->getTimestamp()] = $item[1] ?? 0;
            }
        }

        return $data;
    }

    /**
     * @param string   $fromTables
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return string
     */
    private function addRetentionPolicy(string $fromTables, DateTime $from, DateTime $to): string
    {
        $retention = RetentionFactory::getRetention($from, $to);

        return implode(
            ', ',
            array_map(
                static fn(string $item): string => sprintf('"%s".%s', $retention, $item),
                explode(',', $fromTables)
            )
        );
    }

    /**
     * @param mixed[] $data
     * @param string  $delimiter
     *
     * @return mixed[]
     */
    private static function getConditions(array $data, string $delimiter = 'or'): array
    {
        array_walk(
            $data,
            static function (string &$value, string $key): void {
                $value = sprintf('%s = \'%s\'', $key, $value);
            }
        );

        return [implode(sprintf(' %s ', $delimiter), $data)];
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private static function addStringSeparator(string $string): string
    {
        return sprintf('%s, ', $string);
    }

    /**
     * @param mixed[] $data
     * @param string  $function
     *
     * @return string
     */
    private static function getFunctionForSelect(array $data, string $function): string
    {
        return self::createQuery($data, $function);
    }

    /**
     * @param mixed[] $data
     * @param string  $function
     *
     * @return string
     */
    private static function createQuery(array $data, string $function): string
    {
        array_walk(
            $data,
            static function (string &$value, string $key) use ($function): void {
                $value = sprintf('%s("%s") AS %s', $function, $key, $value);
            }
        );

        return implode(', ', $data);
    }

}
