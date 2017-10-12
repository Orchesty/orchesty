<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/12/17
 * Time: 1:26 PM
 */

namespace Hanaboso\PipesFramework\Commons\Utils;

/**
 * Class Headers
 *
 * @package Hanaboso\PipesFramework\Commons\Pipes\Headers
 */
class PipesHeaders
{

    public const PFP_PREFIX = 'pfp_';
    public const PF_PREFIX  = 'pf_';

    /**
     * @param string $key
     *
     * @return string
     */
    public static function createPermanentKey(string $key): string
    {
        return sprintf('%s%s', self::PFP_PREFIX, $key);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public static function createKey(string $key): string
    {
        return sprintf('%s%s', self::PF_PREFIX, $key);
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    public static function getPermanentHeaders(array $headers): array
    {
        return array_filter(
            $headers,
            function ($key) {
                return self::existPrefix(self::PFP_PREFIX, $key);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    public static function getHeaders(array $headers): array
    {
        return array_filter(
            $headers,
            function ($key) {
                return self::existPrefix(self::PF_PREFIX, $key);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param string $key
     * @param array  $headers
     *
     * @return string|null
     */
    public static function getPermanentHeader(string $key, array $headers): ?string
    {
        return $headers[self::PFP_PREFIX . $key] ?? NULL;
    }

    /**
     * @param string $key
     * @param array  $headers
     *
     * @return string|null
     */
    public static function getHeader(string $key, array $headers): ?string
    {
        return $headers[self::PF_PREFIX . $key] ?? NULL;
    }

    /**
     * @param string $prefix
     * @param string $key
     *
     * @return bool
     */
    private static function existPrefix(string $prefix, string $key): bool
    {
        return strpos($key, $prefix) === 0;
    }

}