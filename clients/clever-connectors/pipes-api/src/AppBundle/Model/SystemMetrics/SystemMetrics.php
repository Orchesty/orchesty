<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\SystemMetrics;

/**
 * Class SystemMetrics
 *
 * @package CleverConnectors\AppBundle\Model\SystemMetrics
 */
class SystemMetrics implements SystemMetricsInterface
{

    /**
     * @param SystemMetricsDto $dto
     *
     * @return array
     */
    public function getSystemMetrics(SystemMetricsDto $dto): array
    {
        // TODO: Implement properly!
        return [];
    }

    /**
     * @param SystemMetricsDto $dto
     *
     * @return int
     */
    public function getSystemRequestCount(SystemMetricsDto $dto): int
    {
        // TODO: Implement properly!
        return 0;
    }

}