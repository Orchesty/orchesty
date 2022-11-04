<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Enum;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class ApiTokenScopesEnum
 *
 * @package Hanaboso\PipesFramework\Configurator\Enum
 */
final class ApiTokenScopesEnum extends EnumAbstract
{

    public const TOPOLOGY_RUN     = 'topology:run';
    public const LOG_WRITE        = 'log:write';
    public const METRIC_WRITE     = 'metric:write';
    public const WORKER_ALL       = 'worker:all';
    public const APPLICATIONS_ALL = 'applications:all';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::TOPOLOGY_RUN     => self::TOPOLOGY_RUN,
        self::LOG_WRITE        => self::LOG_WRITE,
        self::METRIC_WRITE     => self::METRIC_WRITE,
        self::WORKER_ALL       => self::WORKER_ALL,
        self::APPLICATIONS_ALL => self::APPLICATIONS_ALL,
    ];

}
