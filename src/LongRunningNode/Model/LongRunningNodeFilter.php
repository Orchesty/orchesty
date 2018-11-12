<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\LongRunningNode\Model;

use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesFramework\LongRunningNode\Document\LongRunningNodeData;

/**
 * Class LongRunningNodeFilter
 *
 * @package Hanaboso\PipesFramework\LongRunningNode\Model
 */
final class LongRunningNodeFilter extends GridFilterAbstract
{

    /**
     * @var array
     */
    protected $filterCols = [
        LongRunningNodeData::CREATED       => LongRunningNodeData::CREATED,
        LongRunningNodeData::UPDATED       => LongRunningNodeData::UPDATED,
        LongRunningNodeData::TOPOLOGY_NAME => LongRunningNodeData::TOPOLOGY_NAME,
        LongRunningNodeData::NODE_NAME     => LongRunningNodeData::NODE_NAME,
        LongRunningNodeData::AUDIT_LOGS    => LongRunningNodeData::AUDIT_LOGS,
    ];

    /**
     * @var array
     */
    protected $orderCols = [
        LongRunningNodeData::CREATED => LongRunningNodeData::CREATED,
        LongRunningNodeData::UPDATED => LongRunningNodeData::UPDATED,
    ];

    /**
     * @var array
     */
    protected $searchableCols = [
        LongRunningNodeData::AUDIT_LOGS,
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
                LongRunningNodeData::CREATED,
                LongRunningNodeData::UPDATED,
                LongRunningNodeData::AUDIT_LOGS,
                LongRunningNodeData::TOPOLOGY_NAME,
                LongRunningNodeData::NODE_NAME,
                LongRunningNodeData::PROCESS_ID,
            ]);
    }

    /**
     *
     */
    protected function setDocument(): void
    {
        $this->document = LongRunningNodeData::class;
    }

}