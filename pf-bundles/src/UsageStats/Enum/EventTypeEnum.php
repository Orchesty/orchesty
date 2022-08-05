<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\UsageStats\Enum;

use Hanaboso\Utils\Enum\EnumAbstract;

/**
 * Class EventTypeEnum
 *
 * @package Hanaboso\PipesFramework\UsageStats\Enum
 */
final class EventTypeEnum extends EnumAbstract
{

    public const INSTALL    = 'applinth_enduser_app_install';
    public const UNINSTALL  = 'applinth_enduser_app_uninstall';
    public const HEARTHBEAT = 'applinth_enduser_app_hearthbeat';

    /**
     * @var string[]
     */
    protected static array $choices = [
        self::INSTALL    => self::INSTALL,
        self::UNINSTALL  => self::UNINSTALL,
        self::HEARTHBEAT => self::HEARTHBEAT,
    ];

}
