<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class ApplicationInstallException
 *
 * @package Hanaboso\PipesFramework\Application\Exception
 */
final class ApplicationInstallException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 3000;

    public const APPLICATION_INSTALL_NOT_FOUND = self::OFFSET + 1;

}
