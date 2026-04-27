<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler;

use Hanaboso\PipesFrameworkEnterprise\HbPFEnterpriseConfiguratorBundle\Handler\CloudLimitsHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Class CloudLimitsHandlerTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\HbPFEnterpriseConfiguratorBundle\Handler
 */
#[CoversClass(CloudLimitsHandler::class)]
final class CloudLimitsHandlerTest extends TestCase
{

    /**
     * @return mixed[][]
     */
    public static function percentProvider(): array
    {
        return [
            'unlimited returns null'         => [10, 0, NULL],
            'negative limit treated as null' => [10, -1, NULL],
            'half'                           => [50, 100, 50.0],
            'rounded to one decimal'         => [333, 1_000, 33.3],
            'overflow keeps reporting'       => [200, 100, 200.0],
        ];
    }

    /**
     * @param int|float    $current
     * @param int|float    $limit
     * @param float|null   $expected
     */
    #[DataProvider('percentProvider')]
    public function testPercentEdgeCases(int|float $current, int|float $limit, ?float $expected): void
    {
        self::assertSame($expected, CloudLimitsHandler::percent($current, $limit));
    }

    /**
     * @return mixed[][]
     */
    public static function bandProvider(): array
    {
        return [
            'no limit -> none'  => [10, 0, CloudLimitsHandler::BAND_NONE],
            'low usage'         => [10, 100, CloudLimitsHandler::BAND_NONE],
            'just under warn'   => [79, 100, CloudLimitsHandler::BAND_NONE],
            'warning at 80'     => [80, 100, CloudLimitsHandler::BAND_WARNING],
            'critical at 90'    => [90, 100, CloudLimitsHandler::BAND_CRITICAL],
            'critical near 100' => [99, 100, CloudLimitsHandler::BAND_CRITICAL],
            'exceeded at 100'   => [100, 100, CloudLimitsHandler::BAND_EXCEEDED],
            'exceeded above'    => [250, 100, CloudLimitsHandler::BAND_EXCEEDED],
        ];
    }

    /**
     * @param int|float $current
     * @param int|float $limit
     * @param string    $expected
     */
    #[DataProvider('bandProvider')]
    public function testBandThresholds(int|float $current, int|float $limit, string $expected): void
    {
        self::assertSame($expected, CloudLimitsHandler::band($current, $limit));
    }

    public function testBandsToReportSkipsNoneAndConvertsStorageLimit(): void
    {
        $usage = [
            'limits'  => ['messages' => 1_000, 'storageGb' => 10, 'topologySlots' => 0],
            'usage'   => ['messages' => 950, 'storageMb' => 4_000, 'topologySlots' => 1],
            'percent' => ['messages' => 95.0, 'storage' => 39.1, 'slots' => NULL],
            'band'    => [
                'messages' => CloudLimitsHandler::BAND_CRITICAL,
                'storage'  => CloudLimitsHandler::BAND_NONE,
            ],
        ];

        $report = CloudLimitsHandler::bandsToReport($usage);
        self::assertCount(1, $report);
        self::assertSame('messages', $report[0]['resource']);
        self::assertSame(CloudLimitsHandler::BAND_CRITICAL, $report[0]['band']);
        self::assertSame(950, $report[0]['current']);
        self::assertSame(1_000, $report[0]['limit']);
    }

    public function testBandsToReportConvertsStorageLimitToMb(): void
    {
        $usage = [
            'limits'  => ['messages' => 0, 'storageGb' => 10, 'topologySlots' => 0],
            'usage'   => ['messages' => 0, 'storageMb' => 10_500, 'topologySlots' => 0],
            'percent' => ['messages' => NULL, 'storage' => 102.5, 'slots' => NULL],
            'band'    => [
                'messages' => CloudLimitsHandler::BAND_NONE,
                'storage'  => CloudLimitsHandler::BAND_EXCEEDED,
            ],
        ];

        $report = CloudLimitsHandler::bandsToReport($usage);
        self::assertCount(1, $report);
        self::assertSame('storage', $report[0]['resource']);
        self::assertSame(CloudLimitsHandler::BAND_EXCEEDED, $report[0]['band']);
        self::assertSame(10_500, $report[0]['current']);
        self::assertSame(10 * 1_024.0, $report[0]['limit']);
    }

}
