<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Monolog;

use Exception;

/**
 * Class MonologFormatter
 *
 * @package Hanaboso\PipesFramework\Commons\Monolog
 */
class MonologFormatter
{

    /**
     * @param Exception $exception
     *
     * @return string
     */
    public static function formatException(Exception $exception): string
    {
        return sprintf('%s %s: %s', get_class($exception), $exception->getCode(), $exception->getMessage());
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function formatString(string $string): string
    {
        return $string;
    }

}