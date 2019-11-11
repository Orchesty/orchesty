<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Model;

use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesPhpSdk\LongRunningNode\Document\LongRunningNodeData;

/**
 * Class LongRunningNodeFilter
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Model
 */
final class LongRunningNodeFilter extends GridFilterAbstract
{

    /**
     * @var array
     */
    protected $filterCols = [
        LongRunningNodeData::CREATED       => LongRunningNodeData::CREATED,
        LongRunningNodeData::UPDATED       => LongRunningNodeData::UPDATED,
        LongRunningNodeData::TOPOLOGY_ID   => LongRunningNodeData::TOPOLOGY_ID,
        LongRunningNodeData::TOPOLOGY_NAME => LongRunningNodeData::TOPOLOGY_NAME,
        LongRunningNodeData::NODE_ID       => LongRunningNodeData::NODE_ID,
        LongRunningNodeData::NODE_NAME     => LongRunningNodeData::NODE_NAME,
        LongRunningNodeData::AUDIT_LOGS    => LongRunningNodeData::AUDIT_LOGS,
    ];

    /**
     * @var array
     */
    protected $orderCols = [
        LongRunningNodeData::CREATED   => LongRunningNodeData::CREATED,
        LongRunningNodeData::NODE_NAME => LongRunningNodeData::NODE_NAME,
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
            ->select(
                [
                    LongRunningNodeData::CREATED,
                    LongRunningNodeData::UPDATED,
                    LongRunningNodeData::DATA,
                    LongRunningNodeData::AUDIT_LOGS,
                    LongRunningNodeData::TOPOLOGY_ID,
                    LongRunningNodeData::TOPOLOGY_NAME,
                    LongRunningNodeData::NODE_ID,
                    LongRunningNodeData::NODE_NAME,
                    LongRunningNodeData::PROCESS_ID,
                ]
            );
    }

    /**
     *
     */
    protected function setDocument(): void
    {
        $this->document = LongRunningNodeData::class;
    }

}
