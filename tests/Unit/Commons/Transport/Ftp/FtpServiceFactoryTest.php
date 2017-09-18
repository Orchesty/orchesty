<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Transport\Ftp;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\FtpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\SftpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\FtpServiceFactory;
use Tests\KernelTestCaseAbstract;

/**
 * Class FtpServiceFactoryTest
 *
 * @package Tests\Unit\Commons\Transport\Ftp
 */
final class FtpServiceFactoryTest extends KernelTestCaseAbstract
{

    /**
     * @covers FtpServiceFactory::getFtpService()
     */
    public function testGetServiceFtp(): void
    {
        $factory = $this->container->get('hbpf.ftp.service.factory');
        $service = $factory->getFtpService(FtpServiceFactory::ADAPTER_FTP);

        self::assertInstanceOf(FtpAdapter::class, $service->getAdapter());
    }

    /**
     * @covers FtpServiceFactory::getFtpService()
     */
    public function testGetServiceSftp(): void
    {
        $factory = $this->container->get('hbpf.ftp.service.factory');
        $service = $factory->getFtpService(FtpServiceFactory::ADAPTER_SFTP);

        self::assertInstanceOf(SftpAdapter::class, $service->getAdapter());
    }

    /**
     * @covers FtpServiceFactory::getFtpService()
     */
    public function testGetServiceUnknown(): void
    {
        $factory = $this->container->get('hbpf.ftp.service.factory');

        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::UNKNOWN_ADAPTER_TYPE);

        $factory->getFtpService('abc');
    }

}