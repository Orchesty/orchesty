<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesFramework\Logs\Document\Logs;

/**
 * Class LogsFilter
 *
 * @package Hanaboso\PipesFramework\Logs
 */
final class LogsFilter extends GridFilterAbstract
{

    private const SEVERITY = [
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
     * @var array
     */
    protected $filterCols = [
        Logs::ID         => Logs::MONGO_ID,
        'timestamp_from' => 'timestamp>=',
        'timestamp_to'   => 'timestamp<=',
        Logs::MESSAGE    => Logs::MESSAGE,
        'type'           => Logs::PIPES_TYPE,
        'severity'       => Logs::PIPES_SEVERITY,
        'correlation_id' => Logs::PIPES_CORRELATION_ID,
        'topology_id'    => Logs::PIPES_TOPOLOGY_ID,
        'topology_name'  => Logs::PIPES_TOPOLOGY_NAME,
        'node_id'        => Logs::PIPES_NODE_ID,
        'node_name'      => Logs::PIPES_NODE_NAME,
    ];

    /**
     * @var array
     */
    protected $orderCols = [
        Logs::ID         => Logs::MONGO_ID,
        Logs::TIMESTAMP  => Logs::TIMESTAMP,
        Logs::MESSAGE    => Logs::MESSAGE,
        'type'           => Logs::PIPES_TYPE,
        'severity'       => Logs::PIPES_SEVERITY,
        'correlation_id' => Logs::PIPES_CORRELATION_ID,
        'topology_id'    => Logs::PIPES_TOPOLOGY_ID,
        'topology_name'  => Logs::PIPES_TOPOLOGY_NAME,
        'node_id'        => Logs::PIPES_NODE_ID,
        'node_name'      => Logs::PIPES_NODE_NAME,
    ];

    /**
     * @var array
     */
    protected $searchableCols = [
        Logs::MESSAGE,
        'correlation_id',
        'topology_id',
        'topology_name',
        'node_id',
        'node_name',
    ];

    /**
     *
     */
    protected function prepareSearchQuery(): void
    {
        $this->searchQuery = $this
            ->getRepository()
            ->createQueryBuilder()
            ->select([
                Logs::MONGO_ID,
                Logs::TIMESTAMP,
                Logs::MESSAGE,
                Logs::PIPES_TYPE,
                Logs::PIPES_SEVERITY,
                Logs::PIPES_CORRELATION_ID,
                Logs::PIPES_TOPOLOGY_ID,
                Logs::PIPES_TOPOLOGY_NAME,
                Logs::PIPES_NODE_ID,
                Logs::PIPES_NODE_NAME,
            ])
            ->field(Logs::PIPES_SEVERITY)->in(self::SEVERITY);
    }

    /**
     *
     */
    protected function setDocument(): void
    {
        $this->document = Logs::class;
    }

}