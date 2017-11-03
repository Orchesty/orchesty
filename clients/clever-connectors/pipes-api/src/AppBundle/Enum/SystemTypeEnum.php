<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class SystemTypeEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class SystemTypeEnum extends EnumAbstract
{

    public const CRON       = 'cron';
    public const WEBHOOK    = 'webhook';
    public const UI_WEBHOOK = 'ui_webhook';
    public const PLUGIN     = 'plugin';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::CRON       => 'cron',
        self::WEBHOOK    => 'webhook',
        self::UI_WEBHOOK => 'ui_webhook',
        self::PLUGIN     => 'plugin',
    ];

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isWebhook(string $type): bool
    {
        return in_array($type, [self::WEBHOOK, self::UI_WEBHOOK]);
    }

}