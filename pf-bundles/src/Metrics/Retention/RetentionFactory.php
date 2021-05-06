<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Retention;

use DateTime;

/**
 * Class RetentionFactory
 *
 * @package Hanaboso\PipesFramework\Metrics\Retention
 */
final class RetentionFactory
{

    public const SEC       = '5s';
    public const MIN       = '1m';
    public const HALF_HOUR = '30m';
    public const FOUR_HOUR = '4h';

    /**
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return String
     */
    public static function getRetention(DateTime $from, DateTime $to): String
    {
        $diff = $to->modify('-1 second')->diff($from);

        if ($diff->d > 0) {
            return self::FOUR_HOUR;
        } else if ($diff->h > 0) {
            return self::HALF_HOUR;
        } else if ($diff->i > 0) {
            return self::MIN;
        } else {
            return self::SEC;
        }
    }

    /**
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return int
     */
    public static function getRetentionInSeconds(DateTime $from, DateTime $to): int
    {
        return match (self::getRetention($from, $to)) {
            self::MIN => 60,
            self::HALF_HOUR => 30 * 60,
            self::FOUR_HOUR => 4 * 60 * 60,
            default => 5,
        };
    }

}
