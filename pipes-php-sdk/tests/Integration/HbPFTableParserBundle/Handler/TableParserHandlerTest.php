<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFTableParserBundle\Handler;

use Exception;
use Hanaboso\CommonsBundle\Exception\FileStorageException;
use Hanaboso\CommonsBundle\FileStorage\Document\File;
use Hanaboso\CommonsBundle\FileStorage\Dto\FileContentDto;
use Hanaboso\CommonsBundle\FileStorage\Dto\FileStorageDto;
use Hanaboso\CommonsBundle\FileStorage\FileStorage;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesPhpSdk\Parser\Exception\TableParserException;
use Hanaboso\PipesPhpSdk\Parser\TableParser;
use Hanaboso\PipesPhpSdk\Parser\TableParserInterface;
use Hanaboso\Utils\File\File as Files;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class TableParserHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFTableParserBundle\Handler
 */
final class TableParserHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @var TableParserHandler
     */
    private TableParserHandler $handler;

    /**
     * @var string
     */
    private string $path;

    /**
     * @var FileStorage
     */
    private FileStorage $storage;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseToJson
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::getFile
     *
     * @throws Exception
     */
    public function testParseToJson(): void
    {
        $result = $this->handler->parseToJson(
            [
                'file_id'     => sprintf('%s/input-10.xlsx', $this->path),
                'has_headers' => FALSE,
            ],
        );
        self::assertEquals(Files::getContent(sprintf('%s/output-10.json', $this->path)), $result);

        $result = $this->handler->parseToJson(
            [
                'file_id'     => sprintf('%s/input-10h.xlsx', $this->path),
                'has_headers' => TRUE,
            ],
        );
        self::assertEquals(Files::getContent(sprintf('%s/output-10h.json', $this->path)), $result);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::getFile
     * @throws Exception
     */
    public function testGetFile(): void
    {
        $file    = (new File())->setFileUrl('/url/file');
        $storage = self::createPartialMock(FileStorage::class, ['getFileDocument', 'getFileStorage']);
        $storage->expects(self::any())->method('getFileDocument')->willReturn($file);
        $storage
            ->expects(self::any())
            ->method('getFileStorage')
            ->willReturn(new FileStorageDto($file, $file->getFileUrl()));
        $handler = new TableParserHandler(new TableParser(), $storage);
        $isTmp   = FALSE;

        $fileSystem = $this->createPartialMock(Filesystem::class, ['dumpFile']);
        $fileSystem->expects(self::any())->method('dumpFile');

        $path = $this->invokeMethod($handler, 'getFile', [['file_id' => '123'], &$isTmp, $fileSystem]);
        self::assertStringContainsString('/var/www/src/HbPFTableParserBundle/Handler/', $path);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::getFile
     *
     * @throws Exception
     */
    public function testGetFile2(): void
    {
        $file    = (new File())->setFileUrl('/url/file');
        $storage = self::createPartialMock(FileStorage::class, ['getFileDocument', 'getFileStorage']);
        $storage->expects(self::any())->method('getFileDocument')->willReturn($file);
        $storage
            ->expects(self::any())
            ->method('getFileStorage')
            ->willThrowException(new FileStorageException());
        $handler = new TableParserHandler(new TableParser(), $storage);
        $isTmp   = FALSE;

        self::expectException(FileStorageException::class);
        $this->invokeMethod($handler, 'getFile', [['file_id' => '123'], &$isTmp]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::getFile
     *
     * @throws Exception
     */
    public function testGetFileErr(): void
    {
        $isTmp = FALSE;

        self::expectException(TableParserHandlerException::class);
        self::expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->invokeMethod($this->handler, 'getFile', [[], &$isTmp]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseToJson
     *
     * @throws Exception
     */
    public function testParseToJsonFromContent(): void
    {
        $content = Files::getContent(sprintf('%s/input-10.xlsx', $this->path));
        $file    = $this->storage->saveFileFromContent(new FileContentDto($content, 'xlsx'));

        $result = $this->handler->parseToJson(['file_id' => $file->getId()]);
        self::assertEquals(Files::getContent(sprintf('%s/output-10.json', $this->path)), $result);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseToJsonTest
     */
    public function testParseToJsonTest(): void
    {
        self::assertTrue($this->handler->parseToJsonTest());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseFromJson
     *
     * @throws Exception
     */
    public function testParseFromJson(): void
    {
        $resultPath = $this->handler->parseFromJson(
            TableParserInterface::XLSX,
            [
                'file_id'     => sprintf('%s/output-10.json', $this->path),
                'has_headers' => FALSE,
            ],
        );
        $result     = $this->handler->parseToJson(
            [
                'file_id'     => $resultPath,
                'has_headers' => FALSE,
            ],
        );
        self::assertEquals(Files::getContent(sprintf('%s/output-10.json', $this->path)), $result);
        unlink($resultPath);

        $resultPath = $this->handler->parseFromJson(
            TableParserInterface::XLSX,
            [
                'file_id'     => sprintf('%s/output-10h.json', $this->path),
                'has_headers' => TRUE,
            ],
        );
        $result     = $this->handler->parseToJson(
            [
                'file_id'     => $resultPath,
                'has_headers' => TRUE,
            ],
        );
        self::assertEquals(Files::getContent(sprintf('%s/output-10h.json', $this->path)), $result);
        unlink($resultPath);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseFromJson
     *
     * @throws Exception
     */
    public function testParseFromJsonFromContent(): void
    {
        $content = Files::getContent(sprintf('%s/output-10.json', $this->path));
        $file    = $this->storage->saveFileFromContent(new FileContentDto($content, 'json'));

        $resultPath = $this->handler->parseFromJson(
            TableParserInterface::XLSX,
            [
                'file_id' => $file->getId(),
            ],
        );
        $result     = $this->handler->parseToJson(
            [
                'file_id'     => $resultPath,
                'has_headers' => FALSE,
            ],
        );
        self::assertEquals(Files::getContent(sprintf('%s/output-10.json', $this->path)), $result);
        unlink($resultPath);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseFromJsonTest
     * @covers \Hanaboso\PipesPhpSdk\Parser\TableParser::createWriter
     *
     * @throws Exception
     */
    public function testParseFromJsonTest(): void
    {
        self::assertTrue($this->handler->parseFromJsonTest(TableParserInterface::XLSX));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseToJson
     *
     * @throws Exception
     */
    public function testParseToJsonWithoutFile(): void
    {
        self::expectException(TableParserHandlerException::class);
        self::expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseToJson([]);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseFromJson
     *
     * @throws Exception
     */
    public function testParseFromJsonWithoutFile(): void
    {
        self::expectException(TableParserHandlerException::class);
        self::expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseFromJson(TableParserInterface::XLSX, []);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler::parseFromJson
     *
     * @throws Exception
     */
    public function testParseFromJsonWithInvalidType(): void
    {
        self::expectException(TableParserException::class);
        self::expectExceptionCode(TableParserException::UNKNOWN_WRITER_TYPE);
        $this->handler->parseFromJson('Invalid', ['file_id' => sprintf('%s/output-10.json', $this->path)]);
    }

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = self::getContainer()->get('hbpf.file_storage');
        $this->handler = new TableParserHandler(new TableParser(), $this->storage);
        $this->path    = __DIR__ . '/../../Parser/data';
    }

}
