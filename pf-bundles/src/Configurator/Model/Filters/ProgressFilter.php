<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model\Filters;

use Doctrine\ODM\MongoDB\Query\Builder;
use Hanaboso\MongoDataGrid\GridFilterAbstract;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;

/**
 * Class ProgressFilter
 *
 * @package Hanaboso\PipesFramework\Configurator\Model\Filters
 */
final class ProgressFilter extends GridFilterAbstract
{

    protected const DATE_FORMAT = DateTimeUtils::DATE_TIME_GO;

    /**
     * @return string[]
     */
    protected function filterCols(): array
    {
        return [
            'topologyId' => 'topologyId',
            'started'    => 'startedAt',
            'user'       => 'user',
        ];
    }

    /**
     * @return string[]
     */
    protected function orderCols(): array
    {
        return [
            'started'        => 'startedAt',
            'finished'       => 'finishedAt',
            'correlation_id' => 'correlationId',
        ];
    }

    /**
     * @return mixed[]
     */
    protected function searchableCols(): array
    {
        return [];
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
            ->sort('startedAt', 'DESC');
    }

    /**
     * @return void
     */
    protected function setDocument(): void
    {
        $this->document = TopologyProgress::class;
    }

}