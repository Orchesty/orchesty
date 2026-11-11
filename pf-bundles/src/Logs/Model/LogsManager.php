<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs\Model;

use Exception;
use Hanaboso\MongoDataGrid\GridRequestDtoInterface;

/**
 * Class LogsManager
 *
 * @package Hanaboso\PipesFramework\Logs\Model
 */
final readonly class LogsManager
{

    /**
     * LogsManager constructor.
     *
     * @param LogsAggregationFilter $aggregationFilter
     */
    public function __construct(private LogsAggregationFilter $aggregationFilter)
    {
    }

    /**
     * @param GridRequestDtoInterface $dto
     *
     * @return array<mixed>
     * @throws Exception
     */
    public function getLogs(GridRequestDtoInterface $dto): array
    {
        return $this->aggregationFilter->getData($dto)->toArray();
    }

}
