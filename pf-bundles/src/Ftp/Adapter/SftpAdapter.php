<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Ftp\Adapter;

use Hanaboso\PipesFramework\Ftp\Exception\FtpException;
use phpseclib\Net\SFTP;

/**
 * Class SftpAdapter
 *
 * @package Hanaboso\PipesFramework\Ftp\Adapter
 */
class SftpAdapter implements FtpAdapterInterface
{

    /**
     * @var SFTP
     */
    private $sftp;

    /**
     * @param array $params
     *
     * @throws FtpException
     */
    public function connect(array $params): void
    {
        $this->sftp = new SFTP($params['host'], $params['port']);

        if (!$this->sftp instanceof SFTP) {
            throw new FtpException(
                sprintf('Sftp connection to host %s failed.', $params['host']),
                FtpException::CONNECTION_FAILED
            );
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
        if (!$this->sftp->login($username, $password)) {
            throw new FtpException('Login failed.', FtpException::LOGIN_FAILED);
        }
    }

    /**
     *
     */
    public function disconnect(): void
    {
        if ($this->sftp->isConnected()) {
            $this->sftp->disconnect();
            $this->sftp = NULL;
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

        if (!$this->sftp->put($remoteFile, $tmp, SFTP::SOURCE_LOCAL_FILE)) {
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
        if ($this->sftp->get($remoteFile, $localFile) === FALSE) {
            throw new FtpException('File download failed.', FtpException::FILE_DOWNLOAD_FAILED);
        }
    }

    /**
     * @param string $dir
     *
     * @return array
     */
    public function downloadFiles(string $dir): array
    {

    }

}