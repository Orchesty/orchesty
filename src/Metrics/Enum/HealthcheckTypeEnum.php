<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Enum;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class HealthcheckTypeEnum
 *
 * @package Hanaboso\PipesFramework\Metrics\Enum
 */
final class HealthcheckTypeEnum extends EnumAbstract
{

    public const QUEUE   = 'queue';
    public const SERVICE = 'service';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::QUEUE   => 'Queue',
        self::SERVICE => 'Service',
    ];

}
