<?php
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/19/18
 * Time: 1:46 PM
 */

namespace Hanaboso\PipesFramework\HbPFLogsBundle\Handler;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;

class LogsHandler
{

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * LogsHandler constructor.
     *
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @param string $limit
     * @param string $offset
     *
     * @return array
     */
    public function getData(string $limit, string $offset): array
    {
        $colection = $this->dm->getConnection()
            ->selectDatabase('demo')
            ->selectCollection('Logs');

        $query = $colection
            ->createQueryBuilder()
            //->select(['message', '@timestamp', 'pipes.type', 'pipes.severity', 'pipes.correlation_id'])
            ->field('pipes.severity')->in([
                'error', 'warning', 'alert', 'critical', 'ERROR', 'WARNING', 'ALERT', 'CRITICAL',
            ])
            ->limit((int) $limit)
            ->skip((int) $offset)
            ->getQuery();

        $correlationIds = [];
        $result         = [];
        foreach ($query->toArray() as $item) {
            $correlationIds[] = $item['pipes']['correlation_id'] ?? '';
            $result[]         = [
                'id'             => (string) $item['_id'],
                'severity'       => $item['pipes']['severity'],
                'message'        => $item['message'],
                'type'           => $item['pipes']['type'],
                'correlation_id' => $item['pipes']['correlation_id'] ?? '',
                'topology_id'    => $item['pipes']['topology_id'] ?? '',
                'topology_name'  => $item['pipes']['topology_name'] ?? '',
                'node_id'        => $item['pipes']['node_id'] ?? '',
                'node_name'      => $item['pipes']['node_name'] ?? '',
                'timestamp'      => str_replace('"', '', $item['@timestamp']),
            ];
        }

        //var_dump($correlationIds);
        $query2 = $colection->createQueryBuilder()
            ->field('pipes.correlation_id')->in($correlationIds)
            ->field('pipes.type')->equals('starting_point')
            ->getQuery();

        $result2 = $query2->toArray();

        $xxx = [];
        foreach ($result2 as $item) {
            $xxx[$item['pipes']['correlation_id']] = $item;
        }

        foreach ($result as $key => $item) {
            if (array_key_exists('correlation_id', $item) && $item['correlation_id'] != '') {
                if (array_key_exists($item['correlation_id'], $xxx)) {
                    $result[$key]['topology_id']   = $xxx[$item['correlation_id']]['pipes']['topology_id'];
                    $result[$key]['topology_name'] = $xxx[$item['correlation_id']]['pipes']['topology_name'];
                }
            }
            if (array_key_exists('node_id', $item) && $item['node_id'] != '') {
                $colection = $this->dm->getConnection()
                    ->selectDatabase('demo')
                    ->selectCollection('Node');

                $q = $colection->createQueryBuilder()
                    ->field('_id')->equals(new ObjectId($item['node_id']))
                    ->getQuery();

                $result[$key]['node_name'] = $q->getSingleResult()['name'];
            }
        }

        return [
            'limit'  => $limit,
            'offset' => $offset,
            'count'  => (string) $query->count(TRUE),
            'total'  => (string) $query->count(),
            'items'  => $result,
        ];
    }

}