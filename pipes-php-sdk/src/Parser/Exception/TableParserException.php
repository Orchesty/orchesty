<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Parser\Exception;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class TableParserException
 *
 * @package Hanaboso\PipesPhpSdk\Parser\Exception
 */
final class TableParserException extends PipesFrameworkExceptionAbstract
{

    public const UNKNOWN_WRITER_TYPE = self::OFFSET + 1;
    public const PARSER_NOT_EXISTS   = self::OFFSET + 2;

    protected const OFFSET = 800;

}
