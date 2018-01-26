<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\SystemMetrics;

/**
 * Interface SystemMetricsInterface
 *
 * @package CleverConnectors\AppBundle\Model\SystemMetrics
 */
interface SystemMetricsInterface
{

    /**
     * @param SystemMetricsDto $dto
     *
     * @return array
     */
    public function getSystemMetrics(SystemMetricsDto $dto): array;

    /**
     * @param SystemMetricsDto $dto
     *
     * @return int
     */
    public function getSystemRequestCount(SystemMetricsDto $dto): int;

}