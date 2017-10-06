<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 6.10.17
 * Time: 13:19
 */

namespace Hanaboso\PipesFramework\CustomNode\Exception;

use Exception;

/**
 * Class CustomNodeException
 *
 * @package Hanaboso\PipesFramework\CustomNode\Exception
 */
class CustomNodeException extends Exception
{

    public const NO_PROCESS_ACTION = 1;
    public const NO_BATCH_ACTION   = 2;

}