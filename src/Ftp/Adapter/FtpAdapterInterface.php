<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Ftp\Adapter;

use Hanaboso\PipesFramework\Ftp\Exception\FtpException;

/**
 * Interface FtpAdapterInterface
 *
 * @package Hanaboso\PipesFramework\Ftp\Adapter
 */
interface FtpAdapterInterface
{

    /**
     * @param array $params
     */
    public function connect(array $params): void;

    /**
     *
     */
    public function disconnect(): void;

    /**
     * @param string $username
     * @param string $password
     *
     * @throws FtpException
     */
    public function login(string $username, string $password): void;

    /**
     * @param string $remoteFile
     * @param string $localFile
     *
     * @throws FtpException
     */
    public function uploadFile(string $remoteFile, string $localFile): void;

    /**
     * @param string $remoteFile
     * @param string $localFile
     *
     * @throws FtpException
     */
    public function downloadFile(string $remoteFile, string $localFile): void;

    /**
     * @param string $dir
     *
     * @return array
     */
    public function listDir(string $dir): array;

    /**
     * @param string $dir
     *
     * @return bool
     */
    public function dirExists(string $dir): bool;

    /**
     * @param string $dir
     *
     * @return void
     * @throws FtpException
     */
    public function makeDir($dir): void;

    /**
     * @param string $dir
     *
     * @return void
     * @throws FtpException
     */
    public function makeDirRecursive($dir): void;

}