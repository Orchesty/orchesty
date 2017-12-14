<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 12:52
 */

namespace Hanaboso\PipesFramework\Metrics\Exception;

use Exception;

/**
 * Class MetricsException
 *
 * @package Hanaboso\PipesFramework\Metrics\Exception
 */
final class MetricsException extends Exception
{

    protected const OFFSET = 2800;

    public const DB_NOT_EXIST       = self::OFFSET + 1;
    public const NODE_NOT_FOUND     = self::OFFSET + 2;
    public const TOPOLOGY_NOT_FOUND = self::OFFSET + 3;

}