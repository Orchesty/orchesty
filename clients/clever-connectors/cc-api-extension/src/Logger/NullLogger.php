<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 5.10.17
 * Time: 11:37
 */

namespace CcApi\Logger;

use Tracy\ILogger;

/**
 * Class NullLogger
 *
 * @package CcApi\Logger
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