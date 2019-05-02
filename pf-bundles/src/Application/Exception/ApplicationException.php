<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class ApplicationException
 *
 * @package Hanaboso\PipesFramework\Application\Exception
 */
final class ApplicationException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 3000;

    public const APP_ALREADY_INSTALLED = self::OFFSET + 1;
    public const APP_WAS_NOT_FOUND     = self::OFFSET + 2;

}