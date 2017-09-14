<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Ftp;

/**
 * Interface FtpServiceInterface
 *
 * @package Hanaboso\PipesFramework\Ftp
 */
interface FtpServiceInterface
{

    /**
     * @param string $remoteFile
     * @param string $content
     */
    public function uploadFile(string $remoteFile, string $content): void;

    /**
     * @param string $remoteFile
     * @param string $localFile
     */
    public function downloadFile(string $remoteFile, string $localFile): void;

    /**
     * @param string $dir
     *
     * @return array
     */
    public function downloadFiles(string $dir): array;

}