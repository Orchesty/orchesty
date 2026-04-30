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

    /**
     * Verifies that a "day" argument resolves to a full UTC day window.
     */
    public function testDayProducesFullDayWindow(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['day' => '2026-03-12']);

        self::assertInstanceOf(DateTimeImmutable::class, $end);
        self::assertSame('2026-03-12T00:00:00+00:00', $start->format('c'));
        self::assertSame('2026-03-13T00:00:00+00:00', $end->format('c'));
    }

    /**
     * Verifies that an invalid "day" value throws a LogicException.
     */
    public function testInvalidDayShape(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['day' => 'not-a-date']);
    }

    /**
     * Verifies that explicit "from" and "to" arguments return the exact range.
     */
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

    /**
     * Verifies that providing only "from" without "to" is rejected.
     */
    public function testFromAlonePartialRangeRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['from' => '2026-03-01T00:00:00Z']);
    }

    /**
     * Verifies that a "to" earlier than "from" is rejected.
     */
    public function testToBeforeFromRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve([
            'from' => '2026-03-10T00:00:00Z',
            'to'   => '2026-03-01T00:00:00Z',
        ]);
    }

    /**
     * Verifies that period=today resolves to a window starting at midnight today.
     */
    public function testPeriodToday(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'today']);

        self::assertNotNull($end);
        self::assertSame('00:00:00', $start->format('H:i:s'));
        self::assertGreaterThanOrEqual($start, $end);
    }

    /**
     * Verifies that period=yesterday resolves to the full previous calendar day.
     */
    public function testPeriodYesterday(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'yesterday']);

        self::assertNotNull($end);
        self::assertSame('00:00:00', $start->format('H:i:s'));
        self::assertSame('00:00:00', $end->format('H:i:s'));
        self::assertSame(86_400, $end->getTimestamp() - $start->getTimestamp());
    }

    /**
     * Verifies that period=last_7d returns a window approximately seven days wide.
     */
    public function testPeriodLast7d(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'last_7d']);

        self::assertNotNull($end);
        $diffSeconds = $end->getTimestamp() - $start->getTimestamp();
        self::assertGreaterThanOrEqual(7 * 86_400 - 60, $diffSeconds);
        self::assertLessThanOrEqual(7 * 86_400 + 60, $diffSeconds);
    }

    /**
     * Verifies that period=last_30d returns a window approximately thirty days wide.
     */
    public function testPeriodLast30d(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'last_30d']);

        self::assertNotNull($end);
        $diffSeconds = $end->getTimestamp() - $start->getTimestamp();
        self::assertGreaterThanOrEqual(30 * 86_400 - 60, $diffSeconds);
        self::assertLessThanOrEqual(30 * 86_400 + 60, $diffSeconds);
    }

    /**
     * Verifies that period=this_week starts on Monday at midnight.
     */
    public function testPeriodThisWeekStartsOnMonday(): void
    {
        [$start, $end] = DateRangeResolver::resolve(['period' => 'this_week']);

        self::assertNotNull($end);
        self::assertSame('Monday', $start->format('l'));
        self::assertSame('00:00:00', $start->format('H:i:s'));
    }

    /**
     * Verifies that an unknown period name is rejected with LogicException.
     */
    public function testUnknownPeriodRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['period' => 'forever']);
    }

    /**
     * Verifies that combining "day" and "period" arguments is rejected.
     */
    public function testMixingDayAndPeriodRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['day' => '2026-03-12', 'period' => 'today']);
    }

    /**
     * Verifies that omitting all arguments returns the default look-back window.
     */
    public function testDefaultsWhenNothingProvided(): void
    {
        [$start, $end] = DateRangeResolver::resolve([], 7);

        self::assertNull($end);
        $diffSeconds = (new DateTimeImmutable())->getTimestamp() - $start->getTimestamp();
        self::assertGreaterThanOrEqual(7 * 86_400 - 60, $diffSeconds);
        self::assertLessThanOrEqual(7 * 86_400 + 60, $diffSeconds);
    }

    /**
     * Verifies that empty-string values are treated as absent and the default window applies.
     */
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

    /**
     * Verifies that a non-string "day" argument is rejected.
     */
    public function testNonStringValueRejected(): void
    {
        $this->expectException(LogicException::class);

        DateRangeResolver::resolve(['day' => 12]);
    }

}
