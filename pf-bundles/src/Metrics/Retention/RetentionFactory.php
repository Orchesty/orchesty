<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Retention;

use DateTime;
use LogicException;

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
        } elseif ($diff->h > 0) {
            return self::HALF_HOUR;
        } elseif ($diff->i > 0) {
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
        switch (self::getRetention($from, $to)) {
            case self::SEC:
                return 5;
            case self::MIN:
                return 60;
            case self::HALF_HOUR:
                return 30 * 60;
            case self::FOUR_HOUR:
                return 4 * 60 * 60;
            default:
                throw new LogicException('undefined retention map');
        }
    }

}
