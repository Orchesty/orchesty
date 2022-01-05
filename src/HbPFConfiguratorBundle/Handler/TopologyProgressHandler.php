<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler;

use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Model\ProgressManager;

/**
 * Class TopologyProgressHandler
 *
 * @package Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler
 */
final class TopologyProgressHandler
{

    /**
     * TopologyProgressHandler constructor.
     *
     * @param ProgressManager $manager
     */
    public function __construct(private ProgressManager $manager)
    {
    }

    /**
     * @param string $topologyId
     *
     * @return mixed[]
     */
    public function getProgress(string $topologyId): array
    {
        $progresses = $this->manager->getProgress($topologyId);

        return [
            'filter' => [],
            'sorter' => [
                [
                    GridFilterAbstract::COLUMN    => 'id',
                    GridFilterAbstract::DIRECTION => GridFilterAbstract::DESCENDING,
                ],
            ],
            'items'  => $progresses,
            'paging' => [
                'page'         => 1,
                'itemsPerPage' => 20,
                'total'        => count($progresses),
                'nextPage'     => 1,
                'lastPage'     => 1,
                'previousPage' => 1,
            ],
        ];
    }

}
