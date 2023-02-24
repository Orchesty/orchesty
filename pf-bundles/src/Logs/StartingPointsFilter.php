<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Doctrine\ODM\MongoDB\Query\Builder;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesFramework\Logs\Document\Logs;

/**
 * Class StartingPointsFilter
 *
 * @package Hanaboso\PipesFramework\Logs
 */
final class StartingPointsFilter extends GridFilterAbstract
{

    /**
     * @return mixed[]
     */
    protected function filterCols(): array
    {
        return [
            'correlation_id' => Logs::PIPES_CORRELATION_ID,
            'topology_id' => Logs::PIPES_TOPOLOGY_ID,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function orderCols(): array
    {
        return [
            'correlation_id' => Logs::PIPES_CORRELATION_ID,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function searchableCols(): array
    {
        return [
            'correlation_id',
        ];
    }

    /**
     * @return bool
     */
    protected function useTextSearch(): bool
    {
        return FALSE;
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
                    Logs::PIPES_CORRELATION_ID,
                    Logs::PIPES_TOPOLOGY_ID,
                ],
            )
            ->field(Logs::PIPES_SERVICE)->equals('starting_point')
            ->sort(Logs::MONGO_ID, 'DESC');
    }

    /**
     *
     */
    protected function setDocument(): void
    {
        $this->document = Logs::class;
    }

}
