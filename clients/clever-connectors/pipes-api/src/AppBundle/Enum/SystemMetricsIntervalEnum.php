<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class SystemMetricsIntervalEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class SystemMetricsIntervalEnum extends EnumAbstract
{

    public const HOUR  = 'hour';
    public const DAY   = 'day';
    public const WEEK  = 'week';
    public const MONTH = 'month';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::HOUR  => 'hour',
        self::DAY   => 'day',
        self::WEEK  => 'week',
        self::MONTH => 'month',
    ];

}