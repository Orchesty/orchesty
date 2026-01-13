<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Model;

use Exception;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ProcessAggregationFilter;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ProcessGraphAggregationFilter;
use Hanaboso\PipesFramework\Configurator\Model\Filters\ProcessTotalAggregationFilter;
use Hanaboso\PipesFramework\Configurator\Model\Filters\TopologyAggregationFilter;

/**
 * Class ProcessManager
 *
 * @package Hanaboso\PipesFramework\Configurator\Model
 */
final readonly class ProcessManager
{

    /**
     * ProcessManager constructor.
     *
     * @param ProcessAggregationFilter      $processAggregationFilter
     * @param ProcessTotalAggregationFilter $processTotalAggregationFilter
     * @param ProcessGraphAggregationFilter $processGraphAggregationFilter
     * @param TopologyAggregationFilter     $topologyAggregationFilter
     */
    public function __construct(
        private ProcessAggregationFilter $processAggregationFilter,
        private ProcessTotalAggregationFilter $processTotalAggregationFilter,
        private ProcessGraphAggregationFilter $processGraphAggregationFilter,
        private TopologyAggregationFilter $topologyAggregationFilter,
    )
    {
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getProcesses(GridRequestDtoInterface $dto): array
    {
        return $this->processAggregationFilter->getData($dto)->toArray();
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getProcessesTotal(GridRequestDtoInterface $dto): array
    {
        return $this->processTotalAggregationFilter->getData($dto)->toArray();
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getProcessesGraph(GridRequestDtoInterface $dto): array
    {
        return $this->processGraphAggregationFilter->getData($dto)->toArray();
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getProcessesTopologies(GridRequestDtoInterface $dto): array
    {
        return $this->topologyAggregationFilter->getData($dto)->toArray();
    }

}
