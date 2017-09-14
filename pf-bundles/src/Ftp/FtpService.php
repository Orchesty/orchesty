<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Ftp;

/**
 * Class FtpService
 *
 * @package Hanaboso\PipesFramework\Ftp
 */
class FtpService extends FtpServiceAbstract
{

    /**
     * @param string $remoteFile
     * @param string $content
     */
    public function uploadFile(string $remoteFile, string $content): void
    {
        $this->adapter->uploadFile($remoteFile, $content);
    }

    /**
     * @param string $remoteFile
     * @param string $localFile
     */
    public function downloadFile(string $remoteFile, string $localFile): void
    {
        $this->adapter->downloadFile($remoteFile, $localFile);
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    public function downloadFiles(string $dir): array
    {
        $list = $this->adapter->listDir($dir);

        // TODO where do i get file names?
        foreach ($list as $file) {
            $this->adapter->downloadFile($file, '');
        }

        // TODO return array of downloaded files
        return [];
    }

}