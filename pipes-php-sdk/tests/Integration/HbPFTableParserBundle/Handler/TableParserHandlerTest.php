<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFTableParserBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesPhpSdk\Parser\Exception\TableParserException;
use Hanaboso\PipesPhpSdk\Parser\TableParser;
use Hanaboso\PipesPhpSdk\Parser\TableParserInterface;
use Hanaboso\Utils\File\File as Files;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class TableParserHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFTableParserBundle\Handler
 */
#[CoversClass(TableParserHandler::class)]
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
        self::assertSame(Files::getContent(sprintf('%s/output-10.json', $this->path)), $result);

        $result = $this->handler->parseToJson(
            [
                'file_id'     => sprintf('%s/input-10h.xlsx', $this->path),
                'has_headers' => TRUE,
            ],
        );
        self::assertSame(Files::getContent(sprintf('%s/output-10h.json', $this->path)), $result);
    }

    /**
     * @throws Exception
     */
    public function testGetFileErr(): void
    {
        self::expectException(TableParserHandlerException::class);
        self::expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->invokeMethod($this->handler, 'getFile', [[], new Filesystem()]);
    }

    /**
     * @return void
     */
    public function testParseToJsonTest(): void
    {
        self::assertTrue($this->handler->parseToJsonTest());
    }

    /**
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
        self::assertSame(Files::getContent(sprintf('%s/output-10.json', $this->path)), $result);
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
        self::assertSame(Files::getContent(sprintf('%s/output-10h.json', $this->path)), $result);
        unlink($resultPath);
    }

    /**
     * @throws Exception
     */
    public function testParseFromJsonTest(): void
    {
        self::assertTrue($this->handler->parseFromJsonTest(TableParserInterface::XLSX));
    }

    /**
     * @throws Exception
     */
    public function testParseToJsonWithoutFile(): void
    {
        self::expectException(TableParserHandlerException::class);
        self::expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseToJson([]);
    }

    /**
     * @throws Exception
     */
    public function testParseFromJsonWithoutFile(): void
    {
        self::expectException(TableParserHandlerException::class);
        self::expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseFromJson(TableParserInterface::XLSX, []);
    }

    /**
     * @throws Exception
     */
    public function testParseFromJsonWithInvalidType(): void
    {
        self::expectException(TableParserException::class);
        self::expectExceptionCode(TableParserException::UNKNOWN_WRITER_TYPE);
        $this->handler->parseFromJson('Invalid', ['file_id' => sprintf('%s/output-10.json', $this->path)]);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new TableParserHandler(new TableParser());
        $this->path    = __DIR__ . '/../../Parser/data';
    }

}
