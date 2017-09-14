<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Ftp\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class FtpException
 *
 * @package Hanaboso\PipesFramework\Ftp\Exception
 */
class FtpException extends PipesFrameworkException
{

    protected const OFFSET = 2500;

    public const CONNECTION_FAILED       = self::OFFSET + 1;
    public const CONNECTION_CLOSE_FAILED = self::OFFSET + 2;
    public const LOGIN_FAILED            = self::OFFSET + 3;
    public const FILE_UPLOAD_FAILED      = self::OFFSET + 4;
    public const FILE_DOWNLOAD_FAILED    = self::OFFSET + 5;

}