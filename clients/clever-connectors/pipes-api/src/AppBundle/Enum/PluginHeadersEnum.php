<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;

/**
 * Class PluginHeadersEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class PluginHeadersEnum extends PipesHeaders
{

    public const VERSION = 'version';
    public const SYSTEM  = 'system_key';
    public const GUID    = 'user_id';
    public const TOKEN   = 'token';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::VERSION => 'version',
        self::SYSTEM  => 'system_key',
        self::GUID    => 'user_id',
        self::TOKEN   => 'token',
    ];

}