<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Ftp;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\FtpAdapterInterface;
use Nette\Utils\FileSystem;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SplFileInfo;

/**
 * Class FtpService
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Ftp
 */
class FtpService implements FtpServiceInterface, LoggerAwareInterface
{

    /**
     * @var FtpAdapterInterface
     */
    protected $adapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FtpServiceAbstract constructor.
     *
     * @param FtpAdapterInterface $adapter
     */
    public function __construct(FtpAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->logger  = new NullLogger();
    }

    /**
     * @param LoggerInterface $logger
     *
     * @return FtpService
     */
    public function setLogger(LoggerInterface $logger): FtpService
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $host
     * @param bool   $ssl
     * @param int    $port
     * @param int    $timeout
     */
    public function connect(string $host, bool $ssl = FALSE, int $port = 21, $timeout = 15): void
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
     *
     * @return bool
     */
    public function uploadFile(string $remoteFile, string $content): bool
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

        return TRUE;
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