<?php declare(strict_types=1);

namespace CleverCore\Commons\Utils;

use DateTime;
use DateTimeZone;

/**
 * Class DateTimeUtils
 *
 * @package CleverCore\Commons\Utils
 */
final class DateTimeUtils
{

    public const DATETIME = 'Y-m-d H:i:s';
    public const DATE     = 'Y-m-d';
    public const TIME     = 'H:i:s';

    /**
     * @param string $dateTime
     *
     * @return DateTime
     */
    public static function getUTCDate(string $dateTime = 'now'): DateTime
    {
        return new DateTime($dateTime, new DateTimeZone('UTC'));
    }

    /**
     * @param string $dateTime
     * @param string $format
     *
     * @return string
     */
    public static function getUTCDateAsString(string $dateTime = 'now', string $format = self::DATETIME): string
    {
        return (self::getUTCDate($dateTime))->format($format);
    }

}