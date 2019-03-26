<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Parser\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class TableParserException
 *
 * @package Hanaboso\PipesFramework\Parser\Exception
 */
final class TableParserException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 800;

    public const UNKNOWN_WRITER_TYPE = self::OFFSET + 1;
    public const PARSER_NOT_EXISTS   = self::OFFSET + 2;

}