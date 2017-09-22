<?php declare(strict_types=1);

namespace Tests\Manual\Commons\Transport\Ftp\Adapter;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\SftpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\FtpConfig;
use Tests\KernelTestCaseAbstract;

/**
 * Class SftpAdapterTest
 *
 * @package Tests\Manual\Commons\Transport\Ftp\Adapter
 */
final class SftpAdapterTest extends KernelTestCaseAbstract
{

    /**
     * @var SftpAdapter
     */
    private $adapter;

    /**
     * @var FtpConfig
     */
    private $ftpConfig;

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->ftpConfig = new FtpConfig(
            'sftp',
            FALSE,
            22,
            15,
            'sftpuser',
            'sftp'
        );

        $this->adapter = new SftpAdapter();
        $this->adapter->connect($this->ftpConfig);
        $this->adapter->login($this->ftpConfig);
    }

    /**
     * @covers SftpAdapter::connect()
     */
    public function testConnect(): void
    {
        $sftpAdapter = new SftpAdapter();
        $sftpAdapter->connect($this->ftpConfig);
    }

    /**
     * @covers SftpAdapter::login()
     */
    public function testLogin(): void
    {
        $sftpAdapter = new SftpAdapter();
        $sftpAdapter->connect($this->ftpConfig);
        $sftpAdapter->login($this->ftpConfig);
    }

    /**
     * @covers SftpAdapter::login()
     */
    public function testLoginFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::LOGIN_FAILED);

        $sftpAdapter = new SftpAdapter();
        $sftpAdapter->connect($this->ftpConfig);
        $sftpAdapter->login(new FtpConfig('abc', FALSE, 22, 15, 'abc', 'abc'));
    }

    /**
     * @covers SftpAdapter::disconnect()
     */
    public function testDisconnect(): void
    {
        $sftpAdapter = new SftpAdapter();
        $sftpAdapter->connect($this->ftpConfig);
        $sftpAdapter->login($this->ftpConfig);
        $sftpAdapter->disconnect();
    }

    /**
     * @covers SftpAdapter::disconnect()
     */
    public function testDisconnectFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::CONNECTION_NOT_ESTABLISHED);

        $sftpAdapter = new SftpAdapter();
        $sftpAdapter->disconnect();
    }

    /**
     * @covers SftpAdapter::uploadFile()
     * @covers SftpAdapter::downloadFile()
     */
    public function testUploadDownload(): void
    {
        $remoteFile        = '/home/sftpuser/remote-sftp-upload.txt';
        $localFile         = '/tmp/local-sftp-upload.txt';
        $localFileDownload = '/tmp/local-sftp-download.txt';

        // create local file
        file_put_contents($localFile, 'hello');
        self::assertTrue(file_exists($localFile));

        $this->adapter->uploadFile($remoteFile, $localFile);
        $this->adapter->downloadFile($remoteFile, $localFileDownload);

        self::assertTrue(file_exists($localFileDownload));

        $files = $this->adapter->listDir('/home/sftpuser');

        self::assertCount(1, $files);
        self::assertEquals('remote-sftp-upload.txt', $files[0]);

        // remove created files
        unlink($localFile);
        unlink($localFileDownload);
        $this->adapter->remove($remoteFile);
    }

}