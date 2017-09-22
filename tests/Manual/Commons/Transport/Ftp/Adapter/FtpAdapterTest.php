<?php declare(strict_types=1);

namespace Tests\Manual\Commons\Transport\Ftp\Adapter;

use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\FtpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\FtpConfig;
use Tests\KernelTestCaseAbstract;

/**
 * Class FtpAdapterTest
 *
 * @package Manual\Commons\Transport\Ftp\Adapter
 */
final class FtpAdapterTest extends KernelTestCaseAbstract
{

    /**
     * @var FtpAdapter
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
            21,
            15,
            'ftpuser',
            'ftp'
        );

        $this->adapter = new FtpAdapter();
        $this->adapter->connect($this->ftpConfig);
        $this->adapter->login($this->ftpConfig);
    }

    /**
     * @covers FtpAdapter::connect()
     */
    public function testConnect(): void
    {
        $ftpAdapter = new FtpAdapter();
        $ftpAdapter->connect($this->ftpConfig);
    }

    /**
     * @covers FtpAdapter::login()
     */
    public function testLogin(): void
    {
        $ftpAdapter = new FtpAdapter();
        $ftpAdapter->connect($this->ftpConfig);
        $ftpAdapter->login($this->ftpConfig);
    }

    /**
     * @covers FtpAdapter::login()
     */
    public function testLoginFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::LOGIN_FAILED);

        $ftpAdapter = new FtpAdapter();
        $ftpAdapter->connect($this->ftpConfig);
        $ftpAdapter->login(new FtpConfig('abc', FALSE, 22, 15, 'abc', 'abc'));
    }

    /**
     * @covers FtpAdapter::disconnect()
     */
    public function testDisconnect(): void
    {
        $ftpAdapter = new FtpAdapter();
        $ftpAdapter->connect($this->ftpConfig);
        $ftpAdapter->login($this->ftpConfig);
        $ftpAdapter->disconnect();
    }

    /**
     * @covers FtpAdapter::disconnect()
     */
    public function testDisconnectFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::CONNECTION_NOT_ESTABLISHED);

        $ftpAdapter = new FtpAdapter();
        $ftpAdapter->disconnect();
    }

    /**
     * @covers SftpAdapter::uploadFile()
     * @covers SftpAdapter::downloadFile()
     */
    public function testUploadDownload(): void
    {
        $remoteFile        = './remote-ftp-upload.txt';
        $localFile         = '/tmp/local-ftp-upload.txt';
        $localFileDownload = '/tmp/local-ftp-download.txt';

        // create local file
        file_put_contents($localFile, 'hello');
        self::assertTrue(file_exists($localFile));

        $this->adapter->uploadFile($remoteFile, $localFile);
        $this->adapter->downloadFile($remoteFile, $localFileDownload);

        self::assertTrue(file_exists($localFileDownload));

        $files = $this->adapter->listDir('.');

        self::assertCount(1, $files);
        self::assertEquals('remote-ftp-upload.txt', $files[0]);

        // remove created files
        unlink($localFile);
        unlink($localFileDownload);
        $this->adapter->remove($remoteFile);
    }

}