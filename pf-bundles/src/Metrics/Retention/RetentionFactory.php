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
        $to = $to->modify('-1 second');
        $diff  = $to->diff($from);
        $days  = $diff->d;
        $hours = $diff->h;
        $mins  = $diff->i;

        if ($days > 0) {
            return self::FOUR_HOUR;
        } elseif ($hours > 0) {
            return self::HALF_HOUR;
        } elseif ($mins > 0) {
            return self::MIN;
        } else {
            return self::SEC;
        }
    }

}
