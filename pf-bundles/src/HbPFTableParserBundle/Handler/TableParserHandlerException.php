<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFTableParserBundle\Handler;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class TableParserHandlerException
 *
 * @package Hanaboso\PipesFramework\HbPFTableParserBundle\Handler
 */
final class TableParserHandlerException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 400;

    public const PROPERTY_FILE_ID_NOT_SET = self::OFFSET + 1;

}
