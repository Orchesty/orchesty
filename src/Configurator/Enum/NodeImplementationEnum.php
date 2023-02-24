<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Configurator\Enum;

/**
 * Class NodeImplementationEnum
 *
 * @package Hanaboso\PipesFramework\Configurator\Enum
 */
enum NodeImplementationEnum: string
{

    case CONNECTOR = 'connector';
    case CUSTOM    = 'custom';
    case USER      = 'user';
    case BATCH     = 'batch';

}
