<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Metrics;

use Hanaboso\PipesFramework\Commons\Metrics\SystemMetrics;
use PHPUnit\Framework\TestCase;

/**
 * Class SystemMetricsTest
 *
 * @package Tests\Unit\Commons\Metrics
 */
final class SystemMetricsTest extends TestCase
{

    /**
     * @covers SystemMetrics::getCurrentTimestamp()
     */
    public function testGetCurrentTimestamp(): void
    {
        $ts = SystemMetrics::getCurrentTimestamp();
        $this->assertInternalType('int', $ts);

        $ts2 = SystemMetrics::getCurrentTimestamp();
        $this->assertGreaterThanOrEqual($ts, $ts2);
    }

    /**
     * @covers SystemMetrics::getCpuTimes()
     */
    public function testGetCpuTimes(): void
    {
        $before = SystemMetrics::getCpuTimes();
        $this->assertInternalType('array', $before);
        $this->assertArrayHasKey(SystemMetrics::CPU_TIME_USER, $before);
        $this->assertArrayHasKey(SystemMetrics::CPU_TIME_KERNEL, $before);
        $this->assertArrayHasKey(SystemMetrics::CPU_START_TIME, $before);
        $this->assertGreaterThan(0, $before[SystemMetrics::CPU_TIME_USER]);
        $this->assertGreaterThan(0, $before[SystemMetrics::CPU_TIME_KERNEL]);
        $this->assertGreaterThan(0, $before[SystemMetrics::CPU_START_TIME]);

        $cpuUsageBefore = SystemMetrics::getCpuUsage();
        $this->assertGreaterThan(0, $cpuUsageBefore);
    }

}
