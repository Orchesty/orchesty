<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class TableParserHandlerException
 *
 * @package Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler
 */
final class TableParserHandlerException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 400;

    public const PROPERTY_FILE_ID_NOT_SET = self::OFFSET + 1;

}
