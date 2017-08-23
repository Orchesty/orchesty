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

    public const FILE_NOT_FOUND       = self::OFFSET + 1;
    public const INVALID_STORAGE_TYPE = self::OFFSET + 2;
    public const INVALID_FILE_FORMAT  = self::OFFSET + 3;
    public const INVALID_MIMIC_FORMAT = self::OFFSET + 4;

}