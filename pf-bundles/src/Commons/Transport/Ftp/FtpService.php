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
     * @var FtpConfig
     */
    private $ftpConfig;

    /**
     * FtpServiceAbstract constructor.
     *
     * @param FtpAdapterInterface $adapter
     * @param FtpConfig           $ftpConfig
     */
    public function __construct(FtpAdapterInterface $adapter, FtpConfig $ftpConfig)
    {
        $this->adapter   = $adapter;
        $this->ftpConfig = $ftpConfig;
        $this->logger    = new NullLogger();
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
     * @param string $remoteFile
     * @param string $content
     *
     * @return bool
     * @throws FtpException
     */
    public function uploadFile(string $remoteFile, string $content): bool
    {
        $this->connect();
        $this->login();

        if (!$this->adapter->dirExists(dirname($remoteFile))) {
            $this->adapter->makeDirRecursive(dirname($remoteFile));
        }

        $filename = tempnam(sys_get_temp_dir(), 'tmp');
        FileSystem::write($filename, $content);

        try {
            $this->adapter->uploadFile($remoteFile, $filename);
            $this->logger->debug(sprintf('File %s successfully uploaded.', $remoteFile));
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        } finally {
            FileSystem::delete($filename);
            $this->disconnect();
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
        $this->connect();
        $this->login();

        $filename  = basename($remoteFile);
        $localFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;

        try {
            $this->adapter->downloadFile($remoteFile, $localFile);
            $this->logger->debug(sprintf('File %s successfully downloaded to %s.', $remoteFile, $localFile));
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        } finally {
            $this->disconnect();
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
        $this->connect();
        $this->login();

        $downloaded = [];

        try {
            $list = $this->adapter->listDir($dir);
            $this->logger->debug(sprintf('Downloading files from %s directory', $dir));

            foreach ($list as $file) {
                $downloaded[] = $this->downloadFile(trim($dir, '/') . '/' . $file);
            }

            $this->logger->debug('Downloading files finished successfully.');
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        } finally {
            $this->disconnect();
        }

        return $downloaded;
    }

    /**************************************** HELPERS ****************************************/

    /**
     * @throws FtpException
     */
    private function connect(): void
    {
        try {
            $this->adapter->connect($this->ftpConfig);
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     *
     */
    private function disconnect(): void
    {
        try {
            $this->adapter->disconnect();
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * @throws FtpException
     */
    private function login(): void
    {
        try {
            $this->adapter->login($this->ftpConfig);
        } catch (FtpException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

}