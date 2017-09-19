<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Transport\Ftp;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\FtpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\SftpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
     * @var ContainerInterface
     */
    private $container;

    /**
     * FtpServiceFactory constructor.
     *
     * @param FtpAdapter         $ftpAdapter
     * @param SftpAdapter        $sftpAdapter
     * @param LoggerInterface    $logger
     * @param ContainerInterface $container
     */
    public function __construct(
        FtpAdapter $ftpAdapter,
        SftpAdapter $sftpAdapter,
        LoggerInterface $logger,
        ContainerInterface $container
    )
    {
        $this->ftpAdapter  = $ftpAdapter;
        $this->sftpAdapter = $sftpAdapter;
        $this->logger      = $logger;
        $this->container   = $container;
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
                $service = new FtpService($this->ftpAdapter, $this->prepareConfig(self::ADAPTER_FTP));
                break;
            case self::ADAPTER_SFTP:
                $service = new FtpService($this->sftpAdapter, $this->prepareConfig(self::ADAPTER_SFTP));
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

    /**
     * @param string $prefix
     *
     * @return FtpConfig
     */
    private function prepareConfig(string $prefix): FtpConfig
    {
        return new FtpConfig(
            $this->container->getParameter($prefix . '.host'),
            $prefix == self::ADAPTER_FTP ? $this->container->getParameter($prefix . '.ssl') : FALSE,
            $this->container->getParameter($prefix . '.port'),
            $this->container->getParameter($prefix . '.timeout'),
            $this->container->getParameter($prefix . '.user'),
            $this->container->getParameter($prefix . '.password')
        );
    }

}