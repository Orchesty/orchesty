<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use Hanaboso\PipesFramework\Commons\Enum\EnumAbstract;

/**
 * Class PluginHeadersEnum
 *
 * @package CleverConnectors\AppBundle\Enum
 */
final class PluginHeadersEnum extends EnumAbstract
{

    public const VERSION = 'cm-plugin-version';
    public const SYSTEM  = 'cm-system-key';
    public const GUID    = 'cm-guid';
    public const TOKEN   = 'cm-token';

    /**
     * @var string[]
     */
    protected static $choices = [
        self::VERSION => 'cm-plugin-version',
        self::SYSTEM  => 'cm-system-key',
        self::GUID    => 'cm-guid',
        self::TOKEN   => 'cm-token',
    ];

    /**
     * @param string $key
     * @param array  $headers
     *
     * @return mixed
     */
    public static function get(string $key, array $headers)
    {
        $val = $headers[$key] ?? '';

        if (is_array($val)) {
            return $val[0];
        }

        return $val;
    }

}