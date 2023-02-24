<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Enum;

/**
 * Class HealthcheckTypeEnum
 *
 * @package Hanaboso\PipesFramework\Metrics\Enum
 */
enum HealthcheckTypeEnum: string
{

    case QUEUE   = 'queue';
    case SERVICE = 'service';

}
