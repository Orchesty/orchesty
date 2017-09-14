<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Ftp\Adapter;

use Hanaboso\PipesFramework\Ftp\Exception\FtpException;

/**
 * Class FtpAdapter
 *
 * @package Hanaboso\PipesFramework\Ftp\Adapter
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
        if ($params['ssl']) {
            $this->ftp = @ftp_ssl_connect($params['host'], $params['port'], $params['timeout']);
        } else {
            $this->ftp = @ftp_connect($params['host'], $params['port'], $params['timeout']);
        }

        if (!is_resource($this->ftp)) {
            throw new FtpException(
                sprintf('Ftp connection to host %s failed.', $params['host']),
                FtpException::CONNECTION_FAILED
            );
        }
    }

    /**
     * @throws FtpException
     */
    public function disconnect(): void
    {
        if (is_resource($this->ftp)) {
            $res = @ftp_close($this->ftp);
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
        // TODO where do i get the password from? decrypted already?
        $res = @ftp_login($this->ftp, $username, $password);

        if ($res === FALSE) {
            throw new FtpException('Login failed.', FtpException::LOGIN_FAILED);
        }
    }

    /**
     * @param string $remoteFile
     * @param string $content
     *
     * @throws FtpException
     */
    public function uploadFile(string $remoteFile, string $content): void
    {
        $tmp = tmpfile();
        fwrite($tmp, $content);
        fseek($tmp, 0);

        $res = @ftp_put($this->ftp, $remoteFile, $tmp, FTP_BINARY);

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
        $res = @ftp_get($this->ftp, $localFile, $remoteFile, FTP_BINARY);

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
        $list = @ftp_nlist($this->ftp, $dir);

        if ($list === FALSE) {
            throw new FtpException('Failed to list files in directory.');
        }

        return $list;
    }

}