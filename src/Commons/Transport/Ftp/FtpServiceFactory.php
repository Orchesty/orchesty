<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Ftp;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\FtpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\SftpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use Psr\Log\LoggerInterface;

/**
 * Class FtpServiceFactory
 *
 * @package Hanaboso\PipesFramework\Commons\Transport\Ftp
 */
class FtpServiceFactory
{

    public const ADAPTER_FTP  = 'ftp';
    public const ADAPTER_SFTP = 'sftp';

    /**
     * @var FtpAdapter
     */
    private $ftpAdapter;

    /**
     * @var SftpAdapter
     */
    private $sftpAdapter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * FtpServiceFactory constructor.
     *
     * @param FtpAdapter      $ftpAdapter
     * @param SftpAdapter     $sftpAdapter
     * @param LoggerInterface $logger
     */
    public function __construct(FtpAdapter $ftpAdapter, SftpAdapter $sftpAdapter, LoggerInterface $logger)
    {
        $this->ftpAdapter  = $ftpAdapter;
        $this->sftpAdapter = $sftpAdapter;
        $this->logger      = $logger;
    }

    /**
     * @param string $type
     *
     * @return FtpService
     * @throws FtpException
     */
    public function getFtpService(string $type): FtpService
    {
        switch ($type) {
            case self::ADAPTER_FTP:
                $service = new FtpService($this->ftpAdapter);
                break;
            case self::ADAPTER_SFTP:
                $service = new FtpService($this->sftpAdapter);
                break;
            default:
                throw new FtpException(
                    sprintf('Unknown ftp adapter type "%s"', $type),
                    FtpException::UNKNOWN_ADAPTER_TYPE
                );
        }

        $service->setLogger($this->logger);

        return $service;
    }

}