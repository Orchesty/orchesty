<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 17.3.2017
 * Time: 11:50
 */

namespace Hanaboso\PipesFramework\Commons\ServiceStorage;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class ServiceStorageException
 *
 * @package Hanaboso\PipesFramework\Commons\ServiceStorage
 */
class ServiceStorageException extends PipesFrameworkException
{

    protected const OFFSET = 100;

    public const MISSING_OR_INVALID_CONFIGURATION = self::OFFSET + 1;
    public const FAILED_LOAD_DATA                 = self::OFFSET + 2;
    public const FAILED_SAVE_DATA                 = self::OFFSET + 3;
    public const INVALID_STATE                    = self::OFFSET + 4;
    public const DATA_TYPE_NOT_AVAILABLE          = self::OFFSET + 5;

}