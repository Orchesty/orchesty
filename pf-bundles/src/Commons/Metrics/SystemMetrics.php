<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Metrics;

use Exception;

/**
 * Class SystemMetrics
 *
 * @package Hanaboso\PipesFramework\Commons\Metrics
 */
class SystemMetrics
{

    private const HERTZ = 100;

    private const FILE_PROC_UPTIME = '/proc/uptime';
    private const FILE_PROC_STAT   = '/proc/%s/stat';

    public const CPU_TIME_USER   = 'cpu_user_code_time';
    public const CPU_TIME_KERNEL = 'cpu_kernel_code_time';
    public const CPU_START_TIME  = 'cpu_start_time';

    /**
     * Returns current CPU usage in percents
     *
     * Calculation made according to: https://stackoverflow.com/a/16736599/7200406
     *
     * @return float
     */
    public static function getCpuUsage(): float
    {
        try {
            $upTimeContent = file_get_contents(self::FILE_PROC_UPTIME);
            $upTime        = explode(' ', $upTimeContent)[0];

            $cpuTimes  = self::getCpuTimes();
            $totalTime = $cpuTimes[self::CPU_TIME_USER] + $cpuTimes[self::CPU_TIME_KERNEL];
            $seconds   = $upTime - ($cpuTimes[self::CPU_START_TIME] / self::HERTZ);

            return 100 * (($totalTime / self::HERTZ) / $seconds);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * @return array
     */
    public static function getCpuTimes(): array
    {
        try {
            $pid         = getmypid();
            $statFile    = sprintf(self::FILE_PROC_STAT, $pid);
            $statContent = file_get_contents($statFile);
            $stats       = explode(' ', $statContent);

            $uTime     = $stats[13];
            $sTime     = $stats[14];
            $cuTime    = $stats[15];
            $csTime    = $stats[16];
            $startTime = $stats[21];

            return [
                self::CPU_TIME_USER   => $uTime + $cuTime,
                self::CPU_TIME_KERNEL => $sTime + $csTime,
                self::CPU_START_TIME  => $startTime,
            ];
        } catch (Exception $e) {
            return [
                self::CPU_TIME_USER   => 0,
                self::CPU_TIME_KERNEL => 0,
                self::CPU_START_TIME  => 0,
            ];
        }
    }

    /**
     * @return int
     */
    public static function getCurrentTimestamp(): int
    {
        return (int) round(microtime(TRUE) * 1000, 0);
    }

}
