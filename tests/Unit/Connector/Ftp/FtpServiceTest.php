<?php declare(strict_types=1);

namespace Tests\Unit\Connector\Ftp;

use Hanaboso\PipesFramework\Connector\Ftp\Adapter\FtpAdapter;
use Hanaboso\PipesFramework\Connector\Ftp\Exception\FtpException;
use Hanaboso\PipesFramework\Connector\Ftp\FtpService;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use SplFileInfo;

/**
 * Class FtpServiceTest
 *
 * @package Tests\Unit\Connector\Ftp
 */
final class FtpServiceTest extends TestCase
{

    /**
     * @covers FtpService::connect()
     */
    public function testConnectFail(): void
    {
        $adapter = $this->getAdapter('connect', FtpException::CONNECTION_FAILED);
        $this->prepareExpectations(FtpException::CONNECTION_FAILED);

        $service = new FtpService($adapter);
        $service->connect('abc');
    }

    /**
     * @covers FtpService::disconnect()
     */
    public function testDisconnectFail(): void
    {
        $adapter = $this->getAdapter('disconnect', FtpException::CONNECTION_CLOSE_FAILED);
        $this->prepareExpectations(FtpException::CONNECTION_CLOSE_FAILED);

        $service = new FtpService($adapter);
        $service->disconnect();
    }

    /**
     * @covers FtpService::login()
     */
    public function testLoginFail(): void
    {
        $adapter = $this->getAdapter('login', FtpException::LOGIN_FAILED);
        $this->prepareExpectations(FtpException::LOGIN_FAILED);

        $service = new FtpService($adapter);
        $service->login('abc', 'def');
    }

    /**
     * @covers FtpService::uploadFile()
     */
    public function testUploadFile(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|FtpAdapter $adapter */
        $adapter = $this->createPartialMock(FtpAdapter::class, ['dirExists', 'makeDirRecursive', 'uploadFile']);
        $adapter->method('dirExists')->willReturn(FALSE);
        $adapter->method('makeDirRecursive')->willReturn(TRUE);
        $adapter->method('uploadFile')->willReturn(TRUE);

        $service = new FtpService($adapter);
        $result  = $service->uploadFile('abc', 'def');

        self::assertTrue($result);
    }

    /**
     * @covers FtpService::downloadFile()
     */
    public function testDownloadFile(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|FtpAdapter $adapter */
        $adapter = $this->createPartialMock(FtpAdapter::class, ['downloadFile']);
        $adapter->method('downloadFile')->willReturn(TRUE);

        $service = new FtpService($adapter);
        $result = $service->downloadFile('abc');

        self::assertInstanceOf(SplFileInfo::class, $result);
        self::assertEquals('abc', $result->getBasename());
    }

    /**
     * @covers FtpService::downloadFiles()
     */
    public function testDownloadFiles(): void
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|FtpAdapter $adapter */
        $adapter = $this->createPartialMock(FtpAdapter::class, ['listDir', 'downloadFile']);
        $adapter->method('listDir')->willReturn(['abc', 'def']);
        $adapter->method('downloadFile')->willReturn(TRUE);

        $service = new FtpService($adapter);
        $result = $service->downloadFiles('abc');

        self::assertCount(2, $result);
        self::assertInstanceOf(SplFileInfo::class, $result[0]);
        self::assertInstanceOf(SplFileInfo::class, $result[1]);
        self::assertEquals('abc', $result[0]->getBasename());
        self::assertEquals('def', $result[1]->getBasename());
    }

    /**
     * @param string $method
     * @param int    $code
     *
     * @return FtpAdapter|PHPUnit_Framework_MockObject_MockObject
     */
    private function getAdapter(string $method, int $code)
    {
        /** @var PHPUnit_Framework_MockObject_MockObject|FtpAdapter $adapter */
        $adapter = $this->createPartialMock(FtpAdapter::class, [$method]);
        $adapter->method($method)->willThrowException(new FtpException('', $code));

        return $adapter;
    }

    /**
     * @param int $code
     */
    private function prepareExpectations(int $code): void
    {
        self::expectException(FtpException::class);
        self::expectExceptionCode($code);
    }

}