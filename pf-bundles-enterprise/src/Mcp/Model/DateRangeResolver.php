<?php declare(strict_types=1);

namespace Hanaboso\PipesFrameworkEnterprise\Mcp\Model;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use LogicException;

/**
 * Resolves user-friendly date inputs (`day`, `from`/`to`, `period`) emitted by
 * the Trace LLM into a `[start, end?]` tuple of `DateTimeImmutable`.
 *
 * `end` is intentionally optional: when callers omit a range entirely, the
 * resolver returns `[now - $defaultDays, null]` so historical lookups remain
 * open-ended. When callers do provide a range, both ends are returned.
 *
 * @package Hanaboso\PipesFrameworkEnterprise\Mcp\Model
 */
final class DateRangeResolver
{

    public const string KEY_DAY    = 'day';
    public const string KEY_FROM   = 'from';
    public const string KEY_TO     = 'to';
    public const string KEY_PERIOD = 'period';

    public const string PERIOD_TODAY     = 'today';
    public const string PERIOD_YESTERDAY = 'yesterday';
    public const string PERIOD_THIS_WEEK = 'this_week';
    public const string PERIOD_LAST_7D   = 'last_7d';
    public const string PERIOD_LAST_30D  = 'last_30d';

    public const array PERIODS = [
        self::PERIOD_TODAY,
        self::PERIOD_YESTERDAY,
        self::PERIOD_THIS_WEEK,
        self::PERIOD_LAST_7D,
        self::PERIOD_LAST_30D,
    ];

    /**
     * @param mixed[] $args
     * @param int     $defaultDays Used when no range is provided. The resolver
     *                             returns [now - defaultDays, null] so the
     *                             query remains open-ended at the upper bound.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable|null}
     * @throws LogicException on invalid or contradictory inputs.
     */
    public static function resolve(array $args, int $defaultDays = 7): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $day    = self::stringOrNull($args, self::KEY_DAY);
        $from   = self::stringOrNull($args, self::KEY_FROM);
        $to     = self::stringOrNull($args, self::KEY_TO);
        $period = self::stringOrNull($args, self::KEY_PERIOD);

        $provided = array_filter([$day !== NULL, ($from !== NULL || $to !== NULL), $period !== NULL]);
        if (count($provided) > 1) {
            throw new LogicException(
                'Provide at most one of day / from+to / period when specifying a date range.',
            );
        }

        if ($day !== NULL) {
            return self::resolveDay($day);
        }

        if ($from !== NULL || $to !== NULL) {
            return self::resolveFromTo($from, $to);
        }

        if ($period !== NULL) {
            return self::resolvePeriod($period, $now);
        }

        return [$now->modify(sprintf('-%d days', max(1, $defaultDays))), NULL];
    }

    /**
     * @param mixed[] $args
     */
    private static function stringOrNull(array $args, string $key): ?string
    {
        if (!array_key_exists($key, $args)) {
            return NULL;
        }

        $value = $args[$key];
        if ($value === NULL || $value === '') {
            return NULL;
        }

        if (!is_string($value)) {
            throw new LogicException(sprintf('Argument "%s" must be a string.', $key));
        }

        return $value;
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private static function resolveDay(string $day): array
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $day) !== 1) {
            throw new LogicException(
                sprintf('Invalid "day" value "%s". Expected ISO date YYYY-MM-DD.', $day),
            );
        }

        try {
            $start = new DateTimeImmutable($day . 'T00:00:00', new DateTimeZone('UTC'));
        } catch (Exception $e) {
            throw new LogicException(sprintf('Invalid "day" value "%s": %s', $day, $e->getMessage()));
        }

        return [$start, $start->modify('+1 day')];
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private static function resolveFromTo(?string $from, ?string $to): array
    {
        if ($from === NULL || $to === NULL) {
            throw new LogicException('Provide both "from" and "to" when specifying an explicit range.');
        }

        try {
            $start = new DateTimeImmutable($from);
            $end   = new DateTimeImmutable($to);
        } catch (Exception $e) {
            throw new LogicException(sprintf('Invalid date in from/to range: %s', $e->getMessage()));
        }

        if ($end <= $start) {
            throw new LogicException('"to" must be later than "from".');
        }

        return [$start, $end];
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private static function resolvePeriod(string $period, DateTimeImmutable $now): array
    {
        $period = strtolower($period);

        return match ($period) {
            self::PERIOD_TODAY     => [$now->modify('today'), $now],
            self::PERIOD_YESTERDAY => [$now->modify('yesterday'), $now->modify('today')],
            self::PERIOD_THIS_WEEK => [$now->modify('monday this week 00:00:00'), $now],
            self::PERIOD_LAST_7D   => [$now->modify('-7 days'), $now],
            self::PERIOD_LAST_30D  => [$now->modify('-30 days'), $now],
            default                => throw new LogicException(
                sprintf('Unknown period "%s". Supported: %s.', $period, implode(', ', self::PERIODS)),
            ),
        };
    }

}
