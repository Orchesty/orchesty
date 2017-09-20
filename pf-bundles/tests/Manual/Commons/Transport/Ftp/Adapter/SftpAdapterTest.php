<?php declare(strict_types=1);

namespace Tests\Manual\Commons\Transport\Ftp\Adapter;

use Exception;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Adapter\SftpAdapter;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\Exception\FtpException;
use Hanaboso\PipesFramework\Commons\Transport\Ftp\FtpConfig;
use phpseclib\Net\SFTP;
use Tests\KernelTestCaseAbstract;

/**
 * Class SftpAdapterTest
 *
 * @package Tests\Manual\Commons\Transport\Ftp\Adapter
 */
final class SftpAdapterTest extends KernelTestCaseAbstract
{

    /**
     * @var FtpConfig
     */
    private $ftpConfig;

    public function setUp()
    {
        parent::setUp();

        $this->ftpConfig = new FtpConfig(
            'pfbundles_sftp_1',
            FALSE,
            22,
            15,
            'foo',
            'pass'
        );
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
     * @covers SftpAdapter::connect()
     */
    public function testConnectFail(): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode(FtpException::CONNECTION_FAILED);

        $sftpAdapter = new SftpAdapter();
        $sftpAdapter->connect(new FtpConfig('abc', FALSE, 22, 15, 'abc', 'abc'));
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
     */
    public function testUploadFile(): void
    {
        $sftpAdapter = new SftpAdapter();
        $sftpAdapter->connect($this->ftpConfig);
        $sftpAdapter->login($this->ftpConfig);

        //        $connection = ssh2_connect('shell.example.com', 22);
        //        ssh2_auth_password($connection, 'username', 'password');
        //
        //        ssh2_scp_send($connection, '/local/filename', '/remote/filename', 0644);

        $remoteFile = '/tmp/sftp-test-file-remote.txt';
        $localFile  = '/opt/project/pf-bundles/tests/Manual/download/sftp-test-file.txt';

        $sftpAdapter->uploadFile($remoteFile, $localFile);

        $sftpAdapter->disconnect();
    }

    /**
     * @covers SftpAdapter::uploadFile()
     */
    public function testUploadFileSFTPLib(): void
    {
        $sftp = new SFTP('dev.elnino.cz');
        if (!$sftp->login('ricardo', 'hanaboso')) {
            exit('Login Failed');
        }

        $pwd1 = $sftp->exec('pwd');
        $cd   = $sftp->exec('cd /');
        $pwd2 = $sftp->exec('pwd');

        $res1 = $sftp->put('/home/ricardo/abc.abc', 'xxx');
        $res2 = $sftp->put('filename.remote', 'filename.local', SFTP::SOURCE_LOCAL_FILE);

        $logs = $sftp->getSFTPLog();
        $stop = 1;
    }

    /**
     * @covers SftpAdapter::downloadFile()
     */
    public function testDownloadFile(): void
    {
        //        $sftpAdapter = new SftpAdapter();
        //        $sftpAdapter->connect($this->ftpConfig);
        //        $sftpAdapter->login($this->ftpConfig);

        $remoteFile = '/home/foo/download/sftp-test-file.txt';
        $localFile  = '/opt/project/pf-bundles/tests/Manual/download/sftp-test-file-downloaded.txt';

        //        $sftpAdapter->downloadFile($remoteFile, $localFile);
        //
        //        $sftpAdapter->disconnect();

        $connection = ssh2_connect('sftp', 22);
        ssh2_auth_password($connection, 'foo', 'pass');

        ssh2_scp_recv($connection, $remoteFile, $localFile);
    }

    public function test()
    {
        $srcFile = '../test-files/sftp-test-file.txt';
        $dstFile = 'sftp-test-file.txt';

        // Create connection the the remote host
        $conn = ssh2_connect('pfbundles_sftp_1', 22);

        ssh2_auth_password($conn, 'foo', 'pass');

        // Create SFTP session
        $sftp = ssh2_sftp($conn);

        $sftpStream = @fopen('ssh2.sftp://' . $sftp . $dstFile, 'w');

        try {

            if (!$sftpStream) {
                throw new Exception("Could not open remote file: $dstFile");
            }

            $data_to_send = @file_get_contents($srcFile);

            if ($data_to_send === FALSE) {
                throw new Exception("Could not open local file: $srcFile.");
            }

            if (@fwrite($sftpStream, $data_to_send) === FALSE) {
                throw new Exception("Could not send data from file: $srcFile.");
            }

            fclose($sftpStream);

        } catch (Exception $e) {
            error_log('Exception: ' . $e->getMessage());
            fclose($sftpStream);
        }
    }

}