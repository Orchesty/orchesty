<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Utils;

use Hanaboso\PipesFramework\Commons\Enum\MetricsEnum;
use Hanaboso\PipesFramework\Commons\Metrics\InfluxDbSender;
use Hanaboso\PipesFramework\Commons\Metrics\SystemMetrics;

/**
 * Class CurlMetricUtils
 *
 * @package Hanaboso\PipesFramework\Commons\Utils
 */
class CurlMetricUtils
{

    public const KEY_TIMESTAMP        = 'timestamp';
    public const KEY_CPU              = 'cpu';
    public const KEY_REQUEST_DURATION = 'request_duration';
    public const KEY_USER_TIME        = 'user_time';
    public const KEY_KERNEL_TIME      = 'kernel_time';

    /**
     * @param array $startMetrics
     *
     * @return array
     */
    public static function getTimes(array $startMetrics): array
    {
        $startTime      = $startMetrics[self::KEY_TIMESTAMP];
        $startCpuUser   = $startMetrics[self::KEY_CPU][SystemMetrics::CPU_TIME_USER];
        $startCpuKernel = $startMetrics[self::KEY_CPU][SystemMetrics::CPU_TIME_KERNEL];

        $endMetrics = self::getCurrentMetrics();

        return [
            self::KEY_REQUEST_DURATION => $endMetrics[self::KEY_TIMESTAMP] - $startTime,
            self::KEY_USER_TIME        => $endMetrics[self::KEY_CPU][SystemMetrics::CPU_TIME_USER] - $startCpuUser,
            self::KEY_KERNEL_TIME      => $endMetrics[self::KEY_CPU][SystemMetrics::CPU_TIME_KERNEL] - $startCpuKernel,
        ];
    }

    /**
     * @param InfluxDbSender $sender
     * @param array          $timeData
     * @param string         $uri
     */
    public static function sendCurlMetrics(InfluxDbSender $sender, array $timeData, string $uri): void
    {
        $sender->send(
            [
                MetricsEnum::REQUEST_TOTAL_DURATION_SENT => $timeData[self::KEY_REQUEST_DURATION],
            ],
            [
                MetricsEnum::HOST => gethostname(),
                MetricsEnum::URI  => $uri,
            ]
        );
    }

    /**
     * @return array
     */
    public static function getCurrentMetrics(): array
    {
        return [
            self::KEY_TIMESTAMP => SystemMetrics::getCurrentTimestamp(),
            self::KEY_CPU       => SystemMetrics::getCpuTimes(),
        ];
    }

}