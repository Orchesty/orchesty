<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\LongRunningNode\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class LongRunningNodeException
 *
 * @package Hanaboso\PipesPhpSdk\LongRunningNode\Exception
 */
final class LongRunningNodeException extends PipesFrameworkExceptionAbstract
{

    private const OFFSET = 2_700;

    public const LONG_RUNNING_SERVICE_NOT_FOUND  = self::OFFSET + 1;
    public const LONG_RUNNING_DOCUMENT_NOT_FOUND = self::OFFSET + 2;

}