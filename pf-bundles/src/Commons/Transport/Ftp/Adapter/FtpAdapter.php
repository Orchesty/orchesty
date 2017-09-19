<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\FtpServiceInterface;

/**
 * Class FtpAdapter
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter
 */
class FtpAdapter implements FtpAdapterInterface
{

    /**
     * @var resource
     */
    private $ftp;

    /**
     * @param array $params
     *
     * @throws FtpException
     */
    public function connect(array $params): void
    {
        if ($params[FtpServiceInterface::SSL]) {
            $this->ftp = @ftp_ssl_connect(
                $params[FtpServiceInterface::HOST],
                $params[FtpServiceInterface::PORT],
                $params[FtpServiceInterface::TIMEOUT]
            );
        } else {
            $this->ftp = @ftp_connect(
                $params[FtpServiceInterface::HOST],
                $params[FtpServiceInterface::PORT],
                $params[FtpServiceInterface::TIMEOUT]
            );
        }

        if (!is_resource($this->ftp)) {
            throw new FtpException(sprintf('Connection to Ftp server failed.'), FtpException::CONNECTION_FAILED);
        }
    }

    /**
     * @throws FtpException
     */
    public function disconnect(): void
    {
        if (is_resource($this->getResource())) {
            $res = @ftp_close($this->getResource());
            if ($res === FALSE) {
                throw new FtpException('Connection close failed.', FtpException::CONNECTION_CLOSE_FAILED);
            }
        }
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @throws FtpException
     */
    public function login(string $username, string $password): void
    {
        $res = @ftp_login($this->getResource(), $username, $password);

        if ($res === FALSE) {
            throw new FtpException('Login failed.', FtpException::LOGIN_FAILED);
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
        $res = @ftp_put($this->getResource(), $remoteFile, $localFile, FTP_BINARY);

        if ($res === FALSE) {
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
        $res = @ftp_get($this->getResource(), $localFile, $remoteFile, FTP_BINARY);

        if ($res === FALSE) {
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
        $list = @ftp_nlist($this->getResource(), $dir);

        if ($list === FALSE) {
            throw new FtpException('Failed to list files in directory.', FtpException::FILES_LISTING_FAILED);
        }

        return $list;
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public function dirExists(string $dir): bool
    {
        $current = ftp_pwd($this->getResource());
        if (@ftp_chdir($this->getResource(), $dir)) {
            ftp_chdir($this->getResource(), $current);

            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param string $dir
     *
     * @return void
     * @throws FtpException
     */
    public function makeDir($dir): void
    {
        $res = @ftp_mkdir($this->getResource(), $dir);
        if ($res === FALSE) {
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
        $current = @ftp_pwd($this->getResource());
        $parts   = explode('/', trim($dir, '/'));

        foreach ($parts as $part) {
            if (!@ftp_chdir($this->getResource(), $part) && !$this->isFile($part)) {
                $this->makeDir($part);
                ftp_chdir($this->getResource(), $part);
            }
        }

        ftp_chdir($this->getResource(), $current);
    }

    /**************************************** HELPERS ****************************************/

    /**
     * @return resource
     * @throws FtpException
     */
    private function getResource()
    {
        if (is_resource($this->ftp)) {
            return $this->ftp;
        }

        throw new FtpException('Connection to Ftp server not established.', FtpException::CONNECTION_NOT_ESTABLISHED);
    }

    /**
     * @param string $file
     *
     * @return bool
     */
    public function isFile($file): bool
    {
        return @ftp_size($this->getResource(), $file) !== -1;
    }

}