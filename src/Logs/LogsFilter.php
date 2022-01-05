<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\Query\Builder;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesFramework\Logs\Document\Logs;

/**
 * Class LogsFilter
 *
 * @package Hanaboso\PipesFramework\Logs
 */
final class LogsFilter extends GridFilterAbstract
{

    /**
     * @return mixed[]
     */
    protected function filterCols(): array
    {
        return [
            Logs::ID         => Logs::MONGO_ID,
            'timestamp'      => Logs::TIMESTAMP,
            Logs::MESSAGE    => Logs::MESSAGE,
            'type'           => Logs::PIPES_TYPE,
            'severity'       => Logs::PIPES_SEVERITY,
            'correlation_id' => Logs::PIPES_CORRELATION_ID,
            'topology_id'    => Logs::PIPES_TOPOLOGY_ID,
            'topology_name'  => Logs::PIPES_TOPOLOGY_NAME,
            'node_id'        => Logs::PIPES_NODE_ID,
            'node_name'      => Logs::PIPES_NODE_NAME,
            'time_margin'    => Logs::PIPES_TIME_MARGIN,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function orderCols(): array
    {
        return [
            Logs::ID         => Logs::MONGO_ID,
            'timestamp'      => Logs::TIMESTAMP,
            Logs::MESSAGE    => Logs::MESSAGE,
            'type'           => Logs::PIPES_TYPE,
            'severity'       => Logs::PIPES_SEVERITY,
            'correlation_id' => Logs::PIPES_CORRELATION_ID,
            'topology_id'    => Logs::PIPES_TOPOLOGY_ID,
            'topology_name'  => Logs::PIPES_TOPOLOGY_NAME,
            'node_id'        => Logs::PIPES_NODE_ID,
            'node_name'      => Logs::PIPES_NODE_NAME,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function searchableCols(): array
    {
        return [
            Logs::MESSAGE,
            'correlation_id',
            'topology_id',
            'topology_name',
            'node_id',
            'node_name',
        ];
    }

    /**
     * @return bool
     */
    protected function useTextSearch(): bool
    {
        return TRUE;
    }

    /**
     * @return Builder
     */
    protected function prepareSearchQuery(): Builder
    {
        return $this
            ->getRepository()
            ->createQueryBuilder()
            ->select(
                [
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
                ],
            );
    }

    /**
     *
     */
    protected function setDocument(): void
    {
        $this->document = Logs::class;
    }

}
