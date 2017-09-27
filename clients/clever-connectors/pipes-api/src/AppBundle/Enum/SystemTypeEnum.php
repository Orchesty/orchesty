<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class SystemTypeEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
class SystemTypeEnum extends EnumAbstract
{

    public const CRON    = 'cron';
    public const WEBHOOK = 'webhook';
    /**
     * @var string[]
     */
    protected static $choices = [
        self::CRON    => 'cron',
        self::WEBHOOK => 'webhook',
    ];

}