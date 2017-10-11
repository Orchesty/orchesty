<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 13:16
 */

namespace Hanaboso\PipesFramework\TopologyGenerator\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class TopologyGeneratorException
 *
 * @package Hanaboso\PipesFramework\TopologyGenerator\Exception
 */
class TopologyGeneratorException extends PipesFrameworkException
{

    public const RUN_TOPOLOGY_FAILED      = 1;
    public const STOP_TOPOLOGY_FAILED     = 2;
    public const TOPOLOGY_NOT_FOUND       = 3;
    public const TOPOLOGY_ACTION_FOUND    = 4;
    public const GENERATE_TOPOLOGY_FAILED = 5;

}
