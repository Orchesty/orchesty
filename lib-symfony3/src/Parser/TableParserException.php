<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Parser;

use LogicException;

/**
 * Class TableParserException
 *
 * @package Hanaboso\PipesFramework\Parser
 */
final class TableParserException extends LogicException
{

    public const UNKNOWN_WRITER_TYPE = 1;

}