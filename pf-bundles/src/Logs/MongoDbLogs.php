<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/20/18
 * Time: 8:59 AM
 */

namespace Hanaboso\PipesFramework\Logs;

/**
 * Class MongoDbLogs
 *
 * @package Hanaboso\PipesFramework\Logs
 */
class MongoDbLogs implements LogsInterface
{

    /**
     * @var MongoDbStorage
     */
    private $mongoDbStorage;

    /**
     * MongoDbLogs constructor.
     *
     * @param MongoDbStorage $mongoDbStorage
     */
    public function __construct(MongoDbStorage $mongoDbStorage)
    {
        $this->mongoDbStorage = $mongoDbStorage;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function prepareStartingPointItems(array $data): array
    {
        $result = [];
        foreach ($data as $item) {
            $result[$item['pipes']['correlation_id']] = $item;
        }

        return $result;
    }

    /**
     * @param string $limit
     * @param string $offset
     *
     * @return array
     */
    public function getData(string $limit, string $offset): array
    {
        $logsQuery = $this->mongoDbStorage->getLogsQuery($limit, $offset);

        $correlationIds = [];
        $result         = [];
        foreach ($logsQuery->toArray() as $item) {
            $pipes    = $item['pipes'] ?? [];
            $result[] = [
                'id'             => array_key_exists('_id', $item) ? (string) $item['_id'] : '',
                'severity'       => $pipes['severity'] ?? '',
                'message'        => $item['message'] ?? '',
                'type'           => $pipes['type'] ?? '',
                'correlation_id' => $pipes['correlation_id'] ?? '',
                'topology_id'    => $pipes['topology_id'] ?? '',
                'topology_name'  => $pipes['topology_name'] ?? '',
                'node_id'        => $pipes['node_id'] ?? '',
                'node_name'      => $pipes['node_name'] ?? '',
                'timestamp'      => str_replace('"', '', $item['@timestamp'] ?? ''),
            ];

            if (array_key_exists('correlation_id', $pipes) && $pipes['correlation_id'] != '') {
                $correlationIds[] = $pipes['correlation_id'];
            }
        }

        $startingPointData = $this->prepareStartingPointItems(
            $this->mongoDbStorage->getStartingPointQuery($correlationIds)->toArray()
        );

        foreach ($result as $key => $item) {
            if (array_key_exists('correlation_id', $item) && $item['correlation_id'] != '') {
                if (array_key_exists($item['correlation_id'], $startingPointData)) {
                    $result[$key]['topology_id']   = $startingPointData[$item['correlation_id']]['pipes']['topology_id'];
                    $result[$key]['topology_name'] = $startingPointData[$item['correlation_id']]['pipes']['topology_name'];
                }
            }
            if (array_key_exists('node_id', $item) && $item['node_id'] != '') {
                $result[$key]['node_name'] = $this->mongoDbStorage->getNodeData($item['node_id'])['name'] ?? '';
            }
        }

        $count = $logsQuery->count();

        return [
            'limit'  => $limit,
            'offset' => $offset,
            'count'  => (string) $logsQuery->count(TRUE),
            'total'  => $count >= 1000 ? (string) 1000 : (string) $count,
            'items'  => $result,
        ];
    }

}