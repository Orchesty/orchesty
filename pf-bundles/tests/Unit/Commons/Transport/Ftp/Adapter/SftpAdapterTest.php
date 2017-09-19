<?php declare(strict_types=1);

namespace Tests\Unit\Commons\Transport\Ftp\Adapter;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\SftpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use phpseclib\Net\SFTP;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use ReflectionProperty;

/**
 * Class SftpAdapterTest
 *
 * @package Tests\Unit\Connector\Ftp
 */
final class SftpAdapterTest extends TestCase
{

    public function setUp()
    {
        $this->markTestSkipped();
    }


    /**
     * @covers SftpAdapter::login()
     */
    public function testLogin(): void
    {
        $sftp = $this->prepareSftp('login', TRUE);

        $this->prepareAdapter($sftp)->login('', '');
    }

    /**
     * @covers SftpAdapter::login()
     */
    public function testLoginFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::LOGIN_FAILED);

        $sftp = $this->prepareSftp('login', FALSE);

        $this->prepareAdapter($sftp)->login('', '');
    }

    /**
     * @covers SftpAdapter::uploadFile()
     */
    public function testUploadFile(): void
    {
        $sftp = $this->prepareSftp('put', TRUE);

        $this->prepareAdapter($sftp)->uploadFile('', '');
    }

    /**
     * @covers SftpAdapter::uploadFile()
     */
    public function testUploadFileFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::FILE_UPLOAD_FAILED);

        $sftp = $this->prepareSftp('put', FALSE);

        $this->prepareAdapter($sftp)->uploadFile('', '');
    }

    /**
     * @covers SftpAdapter::downloadFile()
     */
    public function testDownloadFile(): void
    {
        $sftp = $this->prepareSftp('get', TRUE);

        $this->prepareAdapter($sftp)->downloadFile('', '');
    }

    /**
     * @covers SftpAdapter::downloadFile()
     */
    public function testDownloadFileFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::FILE_DOWNLOAD_FAILED);

        $sftp = $this->prepareSftp('get', FALSE);

        $this->prepareAdapter($sftp)->downloadFile('', '');
    }

    /**
     * @covers SftpAdapter::listDir()
     */
    public function testListDir(): void
    {
        $list = ['abc', 'def'];
        $sftp = $this->prepareSftp('nlist', $list);

        $result = $this->prepareAdapter($sftp)->listDir('');

        self::assertEquals($list, $result);
    }

    /**
     * @covers SftpAdapter::listDir()
     */
    public function testListDirFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::FILES_LISTING_FAILED);

        $sftp = $this->prepareSftp('nlist', NULL);

        $this->prepareAdapter($sftp)->listDir('');
    }

    /**
     * @covers SftpAdapter::makeDir()
     */
    public function testMakeDir(): void
    {
        $sftp = $this->prepareSftp('mkdir', TRUE);

        $this->prepareAdapter($sftp)->makeDir('');
    }

    /**
     * @covers SftpAdapter::makeDir()
     */
    public function testMakeDirFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::UNABLE_TO_CREATE_DIR);

        $sftp = $this->prepareSftp('mkdir', FALSE);

        $this->prepareAdapter($sftp)->makeDir('');
    }

    /**
     * @param string $method
     * @param mixed  $returnValue
     *
     * @return PHPUnit_Framework_MockObject_MockObject|SFTP
     */
    private function prepareSftp(string $method, $returnValue)
    {
        $sftp = $this->createPartialMock(SFTP::class, ['isConnected', $method]);
        $sftp->method('isConnected')->willReturn(TRUE);
        $sftp->method($method)->willReturn($returnValue);

        return $sftp;
    }

    /**
     * @param PHPUnit_Framework_MockObject_MockObject|SFTP $sftp
     *
     * @return PHPUnit_Framework_MockObject_MockObject|SftpAdapter
     */
    private function prepareAdapter($sftp)
    {
        $adapter = $this->createPartialMock(SftpAdapter::class, ['getResource']);
        $adapter->method('getResource')->willReturn($sftp);

        $propertySftp = new ReflectionProperty($adapter, 'sftp');
        $propertySftp->setAccessible(TRUE);
        $propertySftp->setValue($adapter, $sftp);

        return $adapter;
    }

}