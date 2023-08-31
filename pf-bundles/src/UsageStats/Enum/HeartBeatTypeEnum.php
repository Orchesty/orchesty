<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Enum;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class HeartBeatTypeEnum
 *
 * @package Hanaboso\PipesFramework\UsageStats\Enum
 */
final class HeartBeatTypeEnum extends EnumAbstract
{

    public const START = 'start';
    public const END   = 'end';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::START => self::START,
        self::END   => self::END,
    ];

}
