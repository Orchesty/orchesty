<?php declare(strict_types=1);

namespace PipesFrameworkEnterpriseTests\Integration\Mcp\Model;

use DateTimeImmutable;
use Hanaboso\PipesFrameworkEnterprise\Mcp\Model\DateRangeResolver;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class DateRangeResolverTest
 *
 * @package PipesFrameworkEnterpriseTests\Integration\Mcp\Model
 */
#[CoversClass(DateRangeResolver::class)]
final class DateRangeResolverTest extends TestCase
{

    public function testDayProducesFullDayWindow(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['day' => '2026-03-12']);

        self::assertInstanceOf(DateTimeImmutable::class, $start);
        self::assertInstanceOf(DateTimeImmutable::class, $end);
        self::assertSame('2026-03-12T00:00:00+00:00', $start->format('c'));
        self::assertSame('2026-03-13T00:00:00+00:00', $end->format('c'));
    }

    public function testInvalidDayShape(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['day' => 'not-a-date']);
    }

    public function testFromToReturnsExplicitRange(): void
    {
        [$start, $end] = DateRangeResolver::resolve([
            'from' => '2026-03-01T00:00:00Z',
            'to'   => '2026-03-08T00:00:00Z',
        ]);

        self::assertNotNull($end);
        self::assertSame('2026-03-01T00:00:00+00:00', $start->format('c'));
        self::assertSame('2026-03-08T00:00:00+00:00', $end->format('c'));
    }

    public function testFromAlonePartialRangeRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['from' => '2026-03-01T00:00:00Z']);
    }

    public function testToBeforeFromRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve([
            'from' => '2026-03-10T00:00:00Z',
            'to'   => '2026-03-01T00:00:00Z',
        ]);
    }

    public function testPeriodToday(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'today']);

        self::assertNotNull($end);
        self::assertSame('00:00:00', $start->format('H:i:s'));
        self::assertGreaterThanOrEqual($start, $end);
    }

    public function testPeriodYesterday(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'yesterday']);

        self::assertNotNull($end);
        self::assertSame('00:00:00', $start->format('H:i:s'));
        self::assertSame('00:00:00', $end->format('H:i:s'));
        self::assertSame(86_400, $end->getTimestamp() - $start->getTimestamp());
    }

    public function testPeriodLast7d(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'last_7d']);

        self::assertNotNull($end);
        $diffSeconds = $end->getTimestamp() - $start->getTimestamp();
        self::assertGreaterThanOrEqual(7 * 86_400 - 60, $diffSeconds);
        self::assertLessThanOrEqual(7 * 86_400 + 60, $diffSeconds);
    }

    public function testPeriodLast30d(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'last_30d']);

        self::assertNotNull($end);
        $diffSeconds = $end->getTimestamp() - $start->getTimestamp();
        self::assertGreaterThanOrEqual(30 * 86_400 - 60, $diffSeconds);
        self::assertLessThanOrEqual(30 * 86_400 + 60, $diffSeconds);
    }

    public function testPeriodThisWeekStartsOnMonday(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'this_week']);

        self::assertNotNull($end);
        self::assertSame('Monday', $start->format('l'));
        self::assertSame('00:00:00', $start->format('H:i:s'));
    }

    public function testUnknownPeriodRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['period' => 'forever']);
    }

    public function testMixingDayAndPeriodRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['day' => '2026-03-12', 'period' => 'today']);
    }

    public function testDefaultsWhenNothingProvided(): void
    {
        [$start, $end] = DateRangeResolver::resolve([], 7);

        self::assertNull($end);
        $diffSeconds = (new DateTimeImmutable())->getTimestamp() - $start->getTimestamp();
        self::assertGreaterThanOrEqual(7 * 86_400 - 60, $diffSeconds);
        self::assertLessThanOrEqual(7 * 86_400 + 60, $diffSeconds);
    }

    public function testEmptyStringIsTreatedAsAbsent(): void
    {
        [$start, $end] = DateRangeResolver::resolve([
            'day'    => '',
            'period' => '',
        ], 1);

        self::assertNull($end);
        $diffSeconds = (new DateTimeImmutable())->getTimestamp() - $start->getTimestamp();
        self::assertGreaterThanOrEqual(86_400 - 60, $diffSeconds);
    }

    public function testNonStringValueRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['day' => 12]);
    }

}
