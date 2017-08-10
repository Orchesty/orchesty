<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFTableParserBundle\Handler;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class TableParserHandlerException
 *
 * @package Hanaboso\PipesFramework\HbPFTableParserBundle\Exception
 */
final class TableParserHandlerException extends PipesFrameworkException
{

    protected const OFFSET = 400;

    public const PROPERTY_FILE_ID_NOT_SET = self::OFFSET + 1;

}