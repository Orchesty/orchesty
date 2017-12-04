<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Enum;

/**
 * Class MetricsEnum
 *
 * @package Hanaboso\PipesFramework\Commons\Enum
 */
class MetricsEnum extends EnumAbstract
{

    // Tags
    public const HOST           = 'host';
    public const URI            = 'uri';
    public const CORRELATION_ID = 'correlation_id';
    public const TOPOLOGY_ID    = 'topology_id';

    // Fields
    public const REQUEST_TOTAL_DURATION = 'fpm_request_total_duration';
    public const CPU_USER_TIME          = 'fpm_cpu_user_time';
    public const CPU_KERNEL_TIME        = 'fpm_cpu_kernel_time';

    /**
     * @var string[]
     */
    protected static $choices = [
        // tags
        self::HOST                   => self::HOST,
        self::URI                    => self::URI,
        self::CORRELATION_ID         => self::CORRELATION_ID,
        self::TOPOLOGY_ID            => self::TOPOLOGY_ID,
        // fields
        self::REQUEST_TOTAL_DURATION => self::REQUEST_TOTAL_DURATION,
        self::CPU_USER_TIME          => self::CPU_USER_TIME,
        self::CPU_KERNEL_TIME        => self::CPU_KERNEL_TIME,
    ];

}
