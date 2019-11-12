<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

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
     * @var array
     */
    protected $filterCols = [
        'correlation_id' => Logs::PIPES_CORRELATION_ID,
    ];

    /**
     * @var array
     */
    protected $searchableCols = [
        'correlation_id' => Logs::PIPES_CORRELATION_ID,
    ];

    /**
     * @var array
     */
    protected $orderCols = [
        'correlation_id',
    ];

    /**
     *
     */
    protected function prepareSearchQuery(): void
    {
        $this->searchQuery = $this
            ->getRepository()
            ->createQueryBuilder()
            ->select(
                [
                    Logs::PIPES_CORRELATION_ID,
                    Logs::PIPES_TOPOLOGY_ID,
                    Logs::PIPES_TOPOLOGY_NAME,
                ]
            )
            ->field(Logs::PIPES_TYPE)->equals('starting_point')
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
