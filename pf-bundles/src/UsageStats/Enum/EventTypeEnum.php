<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Enum;

/**
 * Class EventTypeEnum
 *
 * @package Hanaboso\PipesFramework\UsageStats\Enum
 */
enum EventTypeEnum: string
{

    case INSTALL    = 'applinth_enduser_app_install';
    case UNINSTALL  = 'applinth_enduser_app_uninstall';
    case HEARTHBEAT = 'applinth_enduser_app_hearthbeat';

}
