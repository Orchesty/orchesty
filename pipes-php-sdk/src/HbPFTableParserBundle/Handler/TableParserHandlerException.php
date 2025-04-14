<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler;

use Hanaboso\Utils\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class TableParserHandlerException
 *
 * @package Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler
 */
final class TableParserHandlerException extends PipesFrameworkExceptionAbstract
{

    public const int PROPERTY_FILE_ID_NOT_SET = self::OFFSET + 1;

    protected const int OFFSET = 400;

}
