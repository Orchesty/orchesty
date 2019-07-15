<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\CustomNode\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class CustomNodeException
 *
 * @package Hanaboso\PipesPhpSdk\CustomNode\Exception
 */
class CustomNodeException extends PipesFrameworkExceptionAbstract
{

    public const NO_PROCESS_ACTION = 1;
    public const NO_BATCH_ACTION   = 2;

}
