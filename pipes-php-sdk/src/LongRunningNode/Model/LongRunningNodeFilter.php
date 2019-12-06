<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Model;

use Doctrine\ODM\MongoDB\Query\Builder;
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
     * @return mixed[]
     */
    protected function filterCols(): array
    {
        return [
            LongRunningNodeData::CREATED       => LongRunningNodeData::CREATED,
            LongRunningNodeData::UPDATED       => LongRunningNodeData::UPDATED,
            LongRunningNodeData::TOPOLOGY_ID   => LongRunningNodeData::TOPOLOGY_ID,
            LongRunningNodeData::TOPOLOGY_NAME => LongRunningNodeData::TOPOLOGY_NAME,
            LongRunningNodeData::NODE_ID       => LongRunningNodeData::NODE_ID,
            LongRunningNodeData::NODE_NAME     => LongRunningNodeData::NODE_NAME,
            LongRunningNodeData::AUDIT_LOGS    => LongRunningNodeData::AUDIT_LOGS,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function orderCols(): array
    {
        return [
            LongRunningNodeData::CREATED   => LongRunningNodeData::CREATED,
            LongRunningNodeData::NODE_NAME => LongRunningNodeData::NODE_NAME,
        ];
    }

    /**
     * @return string[]
     */
    protected function searchableCols(): array
    {
        return [
            LongRunningNodeData::AUDIT_LOGS,
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
