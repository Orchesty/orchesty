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
    case ENTITY_AUDIT     = 'entity:audit';
    case TRACE_READ       = 'trace:read';
    case TRASH_READ       = 'trash:read';
    case LOG_WRITE        = 'log:write';
    case METRIC_WRITE     = 'metric:write';
    case WORKER_ALL       = 'worker:all';
    case APPLICATIONS_ALL = 'applications:all';

}
