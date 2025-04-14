<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Batch\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class BatchException
 *
 * @package Hanaboso\PipesPhpSdk\Batch\Exception
 */
final class BatchException extends PipesFrameworkExceptionAbstract
{

    public const int BATCH_SERVICE_NOT_FOUND = self::OFFSET + 1;

    protected const int OFFSET = 3_500;

}
