<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/24/17
 * Time: 10:16 AM
 */

namespace Hanaboso\PipesFramework\Commons\Transport\Utils;

/**
 * Class TransportFormatter
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Utils
 */
class TransportFormatter
{

    /**
     * @param array $headers
     *
     * @return string
     */
    public static function headersToString(array $headers): string
    {
        $tmpHeaders = [];
        foreach ($headers as $key => $values) {
            if(is_array($values)) {
                $tmpHeaders[] = sprintf('%s=[%s]', $key, implode(", ", $values));
            }else {
                $tmpHeaders[] = sprintf('%s=%s', $key,  $values);
            }
        }

        return implode(", ", $tmpHeaders);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array  $headers
     * @param string $body
     *
     * @return string
     */
    public static function requestToString(string $method, string $url, array $headers = [], string $body = ''): string
    {
        return sprintf(
            'Request: Method: %s, Uri: %s, Headers: %s, Body: "%s"',
            strtoupper($method),
            $url,
            self::headersToString($headers),
            $body
        );
    }

    /**
     * @param int    $statusCode
     * @param string $reasonPhrase
     * @param array  $headers
     * @param string $body
     *
     * @return string
     */
    public static function responseToString(int $statusCode, string $reasonPhrase, array $headers = [],
                                            string $body = ''): string
    {
        return sprintf(
            'Response: Status Code: %s, Reason Phrase: %s, Headers: %s, Body: "%s"',
            $statusCode,
            $reasonPhrase,
            self::headersToString($headers),
            $body
        );
    }

}