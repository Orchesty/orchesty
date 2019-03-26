<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Category\Exception;

use Hanaboso\CommonsBundle\Exception\PipesFrameworkExceptionAbstract;

/**
 * Class CategoryException
 *
 * @package Hanaboso\PipesFramework\Category\Exception
 */
class CategoryException extends PipesFrameworkExceptionAbstract
{

    protected const OFFSET = 2300;

    public const CATEGORY_NOT_FOUND = self::OFFSET + 1;
    public const CATEGORY_USED      = self::OFFSET + 2;

}
