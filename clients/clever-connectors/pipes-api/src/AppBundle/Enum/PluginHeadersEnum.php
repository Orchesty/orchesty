<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Enum;

use CleverConnectors\AppBundle\Utils\CMHeaders;
use Hanaboso\CommonsBundle\Enum\EnumAbstract;
use LogicException;

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

    /**
     * @param string $key
     *
     * @return string
     */
    public static function toCMHeaders(string $key): string
    {
        switch ($key) {
            case self::GUID:
                return CMHeaders::GUID;
            case self::TOKEN:
                return CMHeaders::TOKEN;
            case self::SYSTEM:
                return CMHeaders::SYSTEM_KEY;
            default:
                throw new LogicException(sprintf('Invalid header key [%s].', $key));
        }
    }

}