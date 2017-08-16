<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Parser;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class TableParserException
 *
 * @package Hanaboso\PipesFramework\Parser
 */
final class TableParserException extends PipesFrameworkException
{

    protected const OFFSET = 800;

    public const UNKNOWN_WRITER_TYPE = self::OFFSET + 1;
    public const PARSER_NOT_EXISTS = self::OFFSET + 2;

}