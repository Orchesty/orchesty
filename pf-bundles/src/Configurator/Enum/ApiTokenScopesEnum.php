<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Enum;

/**
 * Class ApiTokenScopesEnum
 *
 * @package Hanaboso\PipesFramework\Configurator\Enum
 */
enum ApiTokenScopesEnum: string
{

    case TOPOLOGY_RUN     = 'topology:run';
    case LOG_WRITE        = 'log:write';
    case METRIC_WRITE     = 'metric:write';
    case WORKER_ALL       = 'worker:all';
    case APPLICATIONS_ALL = 'applications:all';

}
