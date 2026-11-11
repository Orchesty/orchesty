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
            'correlation_id' => Logs::PIPES_CORRELATION_ID,
            'node_id'        => Logs::PIPES_NODE_ID,
            'service'        => Logs::PIPES_SERVICE,
            'severity'       => Logs::PIPES_SEVERITY,
            'timestamp'      => Logs::TIMESTAMP,
            'topology_id'    => Logs::PIPES_TOPOLOGY_ID,
            'user_id'        => Logs::PIPES_USER_ID,
            Logs::ID         => Logs::MONGO_ID,
            Logs::MESSAGE    => Logs::MESSAGE,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function orderCols(): array
    {
        return [
            'correlation_id' => Logs::PIPES_CORRELATION_ID,
            'node_id'        => Logs::PIPES_NODE_ID,
            'service'        => Logs::PIPES_SERVICE,
            'severity'       => Logs::PIPES_SEVERITY,
            'timestamp'      => Logs::TIMESTAMP,
            'topology_id'    => Logs::PIPES_TOPOLOGY_ID,
            Logs::ID         => Logs::MONGO_ID,
            Logs::MESSAGE    => Logs::MESSAGE,
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
            'node_id',
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
                    Logs::PIPES_SERVICE,
                    Logs::PIPES_SEVERITY,
                    Logs::PIPES_CORRELATION_ID,
                    Logs::PIPES_TOPOLOGY_ID,
                    Logs::PIPES_NODE_ID,
                    Logs::PIPES_TIMESTAMP,
                ],
            )
            ->field(Logs::PIPES_CORRELATION_ID)->exists(TRUE);
    }

    /**
     *
     */
    protected function setDocument(): void
    {
        $this->document = Logs::class;
    }

}
