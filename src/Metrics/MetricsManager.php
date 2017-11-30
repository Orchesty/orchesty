<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 13:36
 */

namespace Hanaboso\PipesFramework\Metrics;

use DateTime;
use Hanaboso\PipesFramework\Configurator\Document\Node;
use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Client\ClientInterface;
use Hanaboso\PipesFramework\TopologyGenerator\GeneratorUtils;
use InfluxDB\Query\Builder;

/**
 * Class MetricsManager
 *
 * @package Hanaboso\PipesFramework\Metrics
 */
class MetricsManager
{

    public const TOPOLOGY          = 'host';
    public const NODE              = 'name';
    public const WAIT_TIME         = 'bridge_job_waiting_duration';
    public const NODE_PROCESS_TIME = 'bridge_job_worker_duration';
    public const TOP_PROCESS_TIME  = 'counter_process_duration';

    /**
     * @var Builder
     */
    private $builder;

    /**
     * @var string
     */
    private $tableName;

    /**
     * MetricsManager constructor.
     *
     * @param ClientInterface $client
     * @param string          $tableName
     */
    public function __construct(ClientInterface $client, string $tableName)
    {
        $this->builder   = $client->getQueryBuilder();
        $this->tableName = $tableName;
    }

    public function getTopologyMetrics(Topology $topology, array $params)
    {
        $from = $params['from'] ?? NULL;
        $to   = $params['to'] ?? NULL;

        $select = self::getCountForSelect([
            self::TOP_PROCESS_TIME => 'top_processed_count',
            self::WAIT_TIME        => 'wait_count',
        ]);
        $select .= ', ';
        $select .= self::getSumForSelect([
            self::TOP_PROCESS_TIME => 'top_processed_sum',
            self::WAIT_TIME        => 'wait_sum',
        ]);

        $qb = $this->builder
            ->select($select)
            ->from($this->tableName)
            ->where([
                self::getCondition(
                    self::TOPOLOGY,
                    GeneratorUtils::createNormalizedServiceName($topology->getId(), $topology->getName())
                ),
            ]);

        if ($from && $to) {
            $qb->setTimeRange((new DateTime($from))->getTimestamp(), (new DateTime($to))->getTimestamp());
        }

        // @TODO agregace výsledků
        return $qb->getQuery();
    }

    public function getNodeMetrics(Node $node, array $params)
    {

    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    private static function getCondition(string $name, string $value): string
    {
        return sprintf('%s = \'%s\'', $name, $value);
    }

    /**
     * @param array $counts
     *
     * @return string
     */
    private static function getCountForSelect(array $counts): string
    {
        return self::createQuery($counts, 'COUNT');
    }

    /**
     * @param array $sums
     *
     * @return string
     */
    private static function getSumForSelect(array $sums): string
    {
        return self::createQuery($sums, 'SUM');
    }

    /**
     * @param array  $data
     * @param string $funcName
     *
     * @return string
     */
    private static function createQuery(array $data, string $funcName): string
    {
        $ret   = '';
        $first = TRUE;
        foreach ($data as $key => $alias) {
            if (!$first) {
                $ret   .= sprintf(', %s("%s") as %s', $funcName, $key, $alias);
                continue;
            }

            $first = FALSE;
            $ret = sprintf('%s("%s") as %s', $funcName, $key, $alias);
        }

        return $ret;
    }

}