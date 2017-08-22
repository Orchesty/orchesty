<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 9:56 AM
 */

namespace Hanaboso\PipesFramework\Commons\Exception;

/**
 * Class FileStorageException
 *
 * @package Hanaboso\PipesFramework\Commons\Exception
 */
class FileStorageException extends PipesFrameworkException
{

    protected const OFFSET = 1500;

    public const FILE_NOT_FOUND = self::OFFSET + 1;

}