<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\FtpConfig;

/**
 * Class SftpAdapter
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter
 */
class SftpAdapter implements FtpAdapterInterface
{

    /**
     * @var resource|bool
     */
    protected $connection;

    /**
     * @var resource|null
     */
    protected $sftp;

    /**
     * @param FtpConfig $ftpConfig
     *
     * @throws FtpException
     */
    public function connect(FtpConfig $ftpConfig): void
    {
        $this->connection = @ssh2_connect($ftpConfig->getHost(), $ftpConfig->getPort());

        if (!is_resource($this->connection)) {
            throw new FtpException(
                sprintf('Sftp connection to host %s failed.', $ftpConfig->getHost()),
                FtpException::CONNECTION_FAILED
            );
        }
    }

    /**
     * @param FtpConfig $ftpConfig
     *
     * @throws FtpException
     */
    public function login(FtpConfig $ftpConfig): void
    {
        if (!@ssh2_auth_password($this->connection, $ftpConfig->getUsername(), $ftpConfig->getPassword())) {
            throw new FtpException('Login failed.', FtpException::LOGIN_FAILED);
        }

//        $this->sftp = @ssh2_sftp($this->connection);
//
//        if (!is_resource($this->sftp)) {
//            throw new FtpException(
//                'Failed creating SFTP subsystem from connection.',
//                FtpException::CREATING_SUBSYSTEM_FAILED
//            );
//        }
    }

    /**
     *
     */
    public function disconnect(): void
    {
        if ($this->getResource()) {
            $this->sftp = NULL;
        }
    }

    /**
     * @param string $remoteFile
     * @param string $localFile
     *
     * @throws FtpException
     */
    public function uploadFile(string $remoteFile, string $localFile): void
    {
        if (!ssh2_scp_send($this->getConnection(), $localFile, $remoteFile, 0644)) {
            throw new FtpException('File upload failed.', FtpException::FILE_UPLOAD_FAILED);
        }
    }

    /**
     * @param string $remoteFile
     * @param string $localFile
     *
     * @throws FtpException
     */
    public function downloadFile(string $remoteFile, string $localFile): void
    {
        if (!ssh2_scp_recv($this->getConnection(), $remoteFile, $localFile)) {
            throw new FtpException('File download failed.', FtpException::FILE_DOWNLOAD_FAILED);
        }
    }

    /**
     * @param string $dir
     *
     * @return array
     * @throws FtpException
     */
    public function listDir(string $dir): array
    {
        $list = $this->scanDir($dir);

        if (!$list) {
            throw new FtpException('Failed to list files in directory.', FtpException::FILES_LISTING_FAILED);
        }

        $files = [];
        foreach ($list as $item) {
            if (!in_array($item, ['.', '..'])) {
                $files[] = $item;
            }
        }

        return $files;
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public function dirExists(string $dir): bool
    {
        return (bool) $this->scanDir($dir);
    }

    /**
     * @param string $dir
     *
     * @return void
     * @throws FtpException
     */
    public function makeDir(string $dir): void
    {
        $mkdir = mkdir($this->preparePath($dir));

        if (!$mkdir) {
            throw new FtpException(
                sprintf('Unable to create directory %s', $dir),
                FtpException::UNABLE_TO_CREATE_DIR
            );
        }
    }

    /**
     * @param string $dir
     *
     * @return void
     * @throws FtpException
     */
    public function makeDirRecursive($dir): void
    {
        $mkdir = mkdir($this->preparePath($dir), 0777, TRUE);

        if (!$mkdir) {
            throw new FtpException(
                sprintf('Unable to create directory %s', $dir),
                FtpException::UNABLE_TO_CREATE_DIR
            );
        }
    }

    /**************************************** HELPERS ****************************************/

    /**
     * @return resource
     * @throws FtpException
     */
    private function getResource()
    {
        if ($this->sftp) {
            return $this->sftp;
        }

        throw new FtpException('Connection to Ftp server not established.', FtpException::CONNECTION_NOT_ESTABLISHED);
    }

    /**
     * @return bool|resource
     */
    private function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    public function isFile($file): bool
    {
        return is_file($this->preparePath($file));
    }

    /**
     * @param string $dir
     *
     * @return array|bool
     */
    private function scanDir(string $dir)
    {
        return @scandir($this->preparePath($dir));
    }

    /**
     * @param string $path
     *
     * @return string
     */
    private function preparePath(string $path): string
    {
        return 'ssh2.sftp://' . $this->getResource() . '/' . trim($path, '/');
    }

}