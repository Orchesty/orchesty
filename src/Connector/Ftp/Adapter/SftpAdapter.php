<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Connector\Ftp\Adapter;

use Hanaboso\PipesFramework\Connector\Ftp\Exception\FtpException;
use phpseclib\Net\SFTP;

/**
 * Class SftpAdapter
 *
 * @package Hanaboso\PipesFramework\Ftp\Adapter
 */
class SftpAdapter implements FtpAdapterInterface
{

    /**
     * @var SFTP|null
     */
    protected $sftp;

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
        if (!$this->getResource()->login($username, $password)) {
            throw new FtpException('Login failed.', FtpException::LOGIN_FAILED);
        }
    }

    /**
     *
     */
    public function disconnect(): void
    {
        if ($this->getResource()) {
            $this->getResource()->disconnect();
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
        if (!$this->getResource()->put($remoteFile, $localFile, SFTP::SOURCE_LOCAL_FILE)) {
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
        if ($this->getResource()->get($remoteFile, $localFile) === FALSE) {
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
        $list = $this->getResource()->nlist($dir);

        if (!$list) {
            throw new FtpException('Failed to list files in directory.', FtpException::FILES_LISTING_FAILED);
        }

        return (array) $list;
    }

    /**
     * @param string $dir
     *
     * @return bool
     */
    public function dirExists(string $dir): bool
    {
        return $this->getResource()->is_dir($dir);
    }

    /**
     * @param string $dir
     *
     * @return void
     * @throws FtpException
     */
    public function makeDir($dir): void
    {
        $mkdir = $this->getResource()->mkdir($dir);

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
        $current = $this->getResource()->pwd();
        $parts   = explode('/', trim($dir, '/'));

        foreach ($parts as $part) {
            if (!$this->getResource()->chdir($part) && !$this->isFile($part)) {
                $this->makeDir($part);
                $this->getResource()->chdir($part);
            }
        }

        $this->getResource()->chdir($current);
    }

    /**************************************** HELPERS ****************************************/

    /**
     * @return SFTP
     * @throws FtpException
     */
    private function getResource(): SFTP
    {
        if ($this->sftp && $this->sftp->isConnected()) {
            return $this->sftp;
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
        return $this->getResource()->is_file($file);
    }

}