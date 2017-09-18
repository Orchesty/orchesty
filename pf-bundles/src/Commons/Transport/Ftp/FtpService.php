<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Ftp;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\FtpAdapterInterface;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
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
     * @return FtpAdapterInterface
     */
    public function getAdapter(): FtpAdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param string $host
     * @param bool   $ssl
     * @param int    $port
     * @param int    $timeout
     *
     * @throws FtpException
     */
    public function connect(string $host, bool $ssl = FALSE, int $port = 21, $timeout = 15): void
    {
        try {
            $this->adapter->connect([
                self::HOST    => trim($host),
                self::PORT    => intval($port),
                self::TIMEOUT => intval($timeout),
                self::SSL     => boolval($ssl),
            ]);
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     *
     */
    public function disconnect(): void
    {
        try {
            $this->adapter->disconnect();
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
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
        try {
            $this->adapter->login($username, $password);
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param string $remoteFile
     * @param string $content
     *
     * @return bool
     * @throws FtpException
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
            $this->logger->info(sprintf('File %s successfully uploaded.', $remoteFile));
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        } finally {
            FileSystem::delete($filename);
        }

        return TRUE;
    }

    /**
     * @param string $remoteFile
     *
     * @return SplFileInfo
     * @throws FtpException
     */
    public function downloadFile(string $remoteFile): SplFileInfo
    {
        $filename  = basename($remoteFile);
        $localFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        try {
            $this->adapter->downloadFile($remoteFile, $localFile);
            $this->logger->info(sprintf('File %s successfully downloaded to %s.', $remoteFile, $localFile));
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return new SplFileInfo($localFile);
    }

    /**
     * @param string $dir
     *
     * @return array
     * @throws FtpException
     */
    public function downloadFiles(string $dir): array
    {
        try {
            $list = $this->adapter->listDir($dir);
            $this->logger->info(sprintf('Downloading files from %s directory', $dir));

            $downloaded = [];
            foreach ($list as $file) {
                $downloaded[] = $this->downloadFile($file);
            }

            $this->logger->info('Downloading files finished successfully.');
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        return $downloaded;
    }

}