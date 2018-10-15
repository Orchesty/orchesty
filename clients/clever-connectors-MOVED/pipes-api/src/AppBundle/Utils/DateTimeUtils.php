<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Utils;

use DateTime;
use DateTimeZone;

/**
 * Class DateTimeUtils
 *
 * @package CleverConnectors\AppBundle\Utils
 */
final class DateTimeUtils
{

    /**
     * @param string $dateTime
     *
     * @return DateTime
     */
    public static function getUTCDateTime(string $dateTime = 'NOW'): DateTime
    {
        return new DateTime($dateTime, new DateTimeZone('UTC'));
    }

    /**
     * @param int $timeStamp
     *
     * @return DateTime
     */
    public static function getUTCDateTimeFromTimeStamp(int $timeStamp = 0): DateTime
    {
        return DateTime::createFromFormat('U', (string) $timeStamp, new DateTimeZone('UTC'));
    }

}