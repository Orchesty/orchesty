<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Enum;

use Hanaboso\CommonsBundle\Enum\EnumAbstract;

/**
 * Class MetricsIntervalEnum
 *
 * @package Hanaboso\PipesFramework\Metrics\Enum
 */
final class MetricsIntervalEnum extends EnumAbstract
{

    public const HOUR  = '1h';
    public const DAY   = '1d';
    public const WEEK  = '1w';
    public const MONTH = '4w';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::HOUR  => '1h',
        self::DAY   => '1d',
        self::WEEK  => '1w',
        self::MONTH => '4w',
    ];

}