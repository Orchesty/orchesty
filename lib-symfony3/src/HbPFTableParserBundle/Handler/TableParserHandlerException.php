<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFTableParserBundle\Handler;

use Hanaboso\PipesFramework\Commons\Exception\PipeFrameworkException;

/**
 * Class TableParserHandlerException
 *
 * @package Hanaboso\PipesFramework\HbPFTableParserBundle\Exception
 */
final class TableParserHandlerException extends PipeFrameworkException
{

    public const PROPERTY_FILE_ID_NOT_SET = 1;

}