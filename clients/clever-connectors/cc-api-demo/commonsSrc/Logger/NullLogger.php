<?php declare(strict_types=1);

namespace CleverCore\Commons\Logger;

use Tracy\ILogger;

/**
 * Class NullLogger
 *
 * @package CleverCore\Commons\Logger
 */
class NullLogger implements ILogger
{

    /**
     * @param mixed  $value
     * @param string $priority
     */
    function log($value, $priority = self::INFO): void
    {
    }

}