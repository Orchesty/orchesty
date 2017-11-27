<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: michal.bartl
 * Date: 11/27/17
 * Time: 1:28 PM
 */

namespace Hanaboso\PipesFramework\Category\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class CategoryException
 *
 * @package Hanaboso\PipesFramework\Category\Exception
 */
class CategoryException extends PipesFrameworkException
{

    protected const OFFSET = 2300;

    public const CATEGORY_NOT_FOUND = self::OFFSET + 1;
    public const CATEGORY_USED      = self::OFFSET + 2;

}