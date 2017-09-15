<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Connector\Ftp;

use SplFileInfo;

/**
 * Interface FtpServiceInterface
 *
 * @package Hanaboso\PipesFramework\Ftp
 */
interface FtpServiceInterface
{

    public const HOST    = 'host';
    public const SSL     = 'ssl';
    public const PORT    = 'port';
    public const TIMEOUT = 'timeout';

    /**
     * @param string $host
     * @param bool   $ssl
     * @param int    $port
     * @param int    $timeout
     */
    public function connect(string $host, bool $ssl = FALSE, int $port = 21, $timeout = 15): void;

    /**
     *
     */
    public function disconnect(): void;

    /**
     * @param string $username
     * @param string $password
     */
    public function login(string $username, string $password): void;

    /**
     * @param string $remoteFile
     * @param string $content
     *
     * @return bool
     */
    public function uploadFile(string $remoteFile, string $content): bool;

    /**
     * @param string $remoteFile
     *
     * @return SplFileInfo
     */
    public function downloadFile(string $remoteFile): SplFileInfo;

    /**
     * @param string $dir
     *
     * @return array
     */
    public function downloadFiles(string $dir): array;

}