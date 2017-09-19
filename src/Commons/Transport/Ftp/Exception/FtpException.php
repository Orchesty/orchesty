<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception;

use Hanaboso\PipesFramework\Commons\Exception\PipesFrameworkException;

/**
 * Class FtpException
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception
 */
class FtpException extends PipesFrameworkException
{

    protected const OFFSET = 2500;

    public const CONNECTION_FAILED          = self::OFFSET + 1;
    public const CONNECTION_CLOSE_FAILED    = self::OFFSET + 2;
    public const LOGIN_FAILED               = self::OFFSET + 3;
    public const FILE_UPLOAD_FAILED         = self::OFFSET + 4;
    public const FILE_DOWNLOAD_FAILED       = self::OFFSET + 5;
    public const CONNECTION_NOT_ESTABLISHED = self::OFFSET + 6;
    public const UNABLE_TO_CREATE_DIR       = self::OFFSET + 7;
    public const FILES_LISTING_FAILED       = self::OFFSET + 8;
    public const UNKNOWN_ADAPTER_TYPE       = self::OFFSET + 9;

}