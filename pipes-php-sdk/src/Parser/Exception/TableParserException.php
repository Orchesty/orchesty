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

    public const int UNKNOWN_WRITER_TYPE = self::OFFSET + 1;
    public const int PARSER_NOT_EXISTS   = self::OFFSET + 2;

    protected const int OFFSET = 800;

}
