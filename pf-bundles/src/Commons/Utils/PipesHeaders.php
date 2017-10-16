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

    public const PF_PREFIX = 'pf-';

    // Framework headers
    public const CORRELATION_ID = 'correlation-id';
    public const PROCESS_ID     = 'process-id';
    public const PARENT_ID      = 'parent-id';
    public const SEQUENCE_ID    = 'sequence-id';
    public const NODE_ID        = 'node-id';
    public const NODE_NAME      = 'node-name';
    public const TOPOLOGY_ID    = 'topology-id';
    public const TOPOLOGY_NAME  = 'topology-name';
    public const RESULT_CODE    = 'result-code';
    public const RESULT_STATUS  = 'result-status';
    public const RESULT_MESSAGE = 'result-message';
    public const RESULT_DETAIL  = 'result-detail';

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
    public static function clear(array $headers): array
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
    public static function get(string $key, array $headers): ?string
    {
        return $headers[self::PF_PREFIX . $key] ?? NULL;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    public static function debugInfo(array $headers): array
    {
        // Find debug header
        $debugInfo = array_filter(
            $headers,
            function ($key) {
                return
                    self::existPrefix(self::PF_PREFIX, $key) &&
                    in_array($key, [self::createKey(self::CORRELATION_ID), self::createKey(self::NODE_ID)]);

            },
            ARRAY_FILTER_USE_KEY
        );

        // remove prefix from header
        foreach ($debugInfo as $key => $value) {
            $debugInfo[str_replace('-', '_', substr($key, strlen(self::PF_PREFIX)))] = $value;
            unset($debugInfo[$key]);
        }

        return $debugInfo;
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