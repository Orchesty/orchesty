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

    public const DB_NOT_EXIST = 1;

}