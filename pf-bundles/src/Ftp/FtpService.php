<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Ftp;

use Hanaboso\PipesFramework\Ftp\Adapter\FtpAdapterInterface;
use Nette\Utils\FileSystem;
use SplFileInfo;

/**
 * Class FtpService
 *
 * @package Hanaboso\PipesFramework\Ftp
 */
class FtpService implements FtpServiceInterface
{

    /**
     * @var FtpAdapterInterface
     */
    protected $adapter;

    /**
     * FtpServiceAbstract constructor.
     *
     * @param FtpAdapterInterface $adapter
     */
    public function __construct(FtpAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param string $host
     * @param bool   $ssl
     * @param int    $port
     * @param int    $timeout
     */
    public function connect(string $host, bool $ssl, int $port = 21, $timeout = 15): void
    {
        $this->adapter->connect([
            self::HOST    => trim($host),
            self::PORT    => intval($port),
            self::TIMEOUT => intval($timeout),
            self::SSL     => boolval($ssl),
        ]);
    }

    /**
     *
     */
    public function disconnect(): void
    {
        $this->adapter->disconnect();
    }

    /**
     * @param string $username
     * @param string $password
     */
    public function login(string $username, string $password): void
    {
        $this->adapter->login($username, $password);
    }

    /**
     * @param string $remoteFile
     * @param string $content
     */
    public function uploadFile(string $remoteFile, string $content): void
    {
        if (!$this->adapter->dirExists(dirname($remoteFile))) {
            $this->adapter->makeDirRecursive(dirname($remoteFile));
        }

        $filename = tempnam(sys_get_temp_dir(), 'tmp');
        FileSystem::write($filename, $content);

        try {
            $this->adapter->uploadFile($remoteFile, $filename);
        } finally {
            FileSystem::delete($filename);
        }
    }

    /**
     * @param string $remoteFile
     *
     * @return SplFileInfo
     */
    public function downloadFile(string $remoteFile): SplFileInfo
    {
        $filename  = basename($remoteFile);
        $localFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        $this->adapter->downloadFile($remoteFile, $localFile);

        return new SplFileInfo($localFile);
    }

    /**
     * @param string $dir
     *
     * @return SplFileInfo[]
     */
    public function downloadFiles(string $dir): array
    {
        $list = $this->adapter->listDir($dir);

        $downloaded = [];
        foreach ($list as $file) {
            $downloaded[] = $this->downloadFile($file);
        }

        return $downloaded;
    }

}