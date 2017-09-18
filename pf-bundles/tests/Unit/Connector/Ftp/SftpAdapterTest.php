<?php declare(strict_types=1);

namespace Tests\Unit\Connector\Ftp;

use Hanaboso\PipesFramework\Connector\Ftp\Adapter\FtpAdapter;
use Hanaboso\PipesFramework\Connector\Ftp\Adapter\SftpAdapter;
use phpseclib\Net\SFTP;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Class SftpAdapterTest
 *
 * @package Tests\Unit\Connector\Ftp
 */
final class SftpAdapterTest extends TestCase
{

    /**
     * @covers FtpAdapter::login()
     */
    public function testLogin(): void
    {
        $sftp = $this->createPartialMock(SftpAdapter::class, ['isConnected', 'login']);
        $sftp->method('isConnected')->willReturn(TRUE);
        $sftp->method('login')->willReturn(TRUE);

        //$adapter = new SftpAdapter();
        $adapter = $this->createPartialMock(SftpAdapter::class, ['getResource']);
        $adapter->method('getResource')->willReturn($sftp);

        $propertySftp = new ReflectionProperty($adapter, 'sftp');
        $propertySftp->setAccessible(TRUE);
        $propertySftp->setValue($adapter, $sftp);

        $adapter->login('', '');
    }

    /**
     * @covers FtpAdapter::downloadFile()
     */
    public function testDownloadFile(): void
    {
        $sftp = $this->createPartialMock(SftpAdapter::class, ['']);
    }

}