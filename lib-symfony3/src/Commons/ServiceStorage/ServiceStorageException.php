<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 11:50
 */

namespace Hanaboso\PipesFramework\Commons\ServiceStorage;

use Hanaboso\PipesFramework\Commons\Exception\PipeFrameworkException;

/**
 * Class ServiceStorageException
 *
 * @package Hanaboso\PipesFramework\Commons\ServiceStorage
 */
class ServiceStorageException extends PipeFrameworkException
{

    public const SERVICE_STORAGE_OFFSET = 0x0100;

    public const MISSING_OR_INVALID_CONFIGURATION = self::SERVICE_STORAGE_OFFSET + 0x01;
    public const FAILED_LOAD_DATA                 = self::SERVICE_STORAGE_OFFSET + 0x02;
    public const FAILED_SAVE_DATA                 = self::SERVICE_STORAGE_OFFSET + 0x03;
    public const INVALID_STATE                    = self::SERVICE_STORAGE_OFFSET + 0x04;
    public const DATA_TYPE_NOT_AVAILABLE          = self::SERVICE_STORAGE_OFFSET + 0x05;

}