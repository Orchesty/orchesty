<?php declare(strict_types=1);

namespace Tests\Integration\HbPFTableParserBundle\Handler;

use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Hanaboso\PipesFramework\Parser\TableParser;
use Hanaboso\PipesFramework\Parser\TableParserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class TableParserHandlerTest
 *
 * @package Tests\Integration\HbPFTableParserBundle\Handler
 */
final class TableParserHandlerTest extends TestCase
{

    /**
     * @var TableParserHandler
     */
    private $handler;

    /**
     * @var string
     */
    private $path;

    /**
     * TableParserHandlerTest constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->handler = new TableParserHandler(new TableParser());
        $this->path    = sprintf('%s/../../Parser/data', __DIR__);
    }

    /**
     * @covers TableParserHandler::parseToJson()
     */
    public function testParseToJson(): void
    {
        $result = $this->handler->parseToJson([
            'file_id'     => sprintf('%s/input-10.xlsx', $this->path),
            'has_headers' => FALSE,
        ]);
        $this->assertEquals(file_get_contents(sprintf('%s/output-10.json', $this->path)), $result);

        $result = $this->handler->parseToJson([
            'file_id'     => sprintf('%s/input-10h.xlsx', $this->path),
            'has_headers' => TRUE,
        ]);
        $this->assertEquals(file_get_contents(sprintf('%s/output-10h.json', $this->path)), $result);
    }

    /**
     * @covers TableParserHandler::parseToJsonTest()
     */
    public function testParseToJsonTest(): void
    {
        self::assertTrue($this->handler->parseToJsonTest());
    }

    /**
     * @covers TableParserHandler::parseFromJson()
     */
    public function testParseFromJson(): void
    {
        $resultPath = $this->handler->parseFromJson(TableParserInterface::XLSX, [
            'file_id'     => sprintf('%s/output-10.json', $this->path),
            'has_headers' => FALSE,
        ]);
        $result     = $this->handler->parseToJson([
            'file_id'     => $resultPath,
            'has_headers' => FALSE,
        ]);
        $this->assertEquals(file_get_contents(sprintf('%s/output-10.json', $this->path)), $result);
        unlink($resultPath);

        $resultPath = $this->handler->parseFromJson(TableParserInterface::XLSX, [
            'file_id'     => sprintf('%s/output-10h.json', $this->path),
            'has_headers' => TRUE,
        ]);
        $result     = $this->handler->parseToJson([
            'file_id'     => $resultPath,
            'has_headers' => TRUE,
        ]);
        $this->assertEquals(file_get_contents(sprintf('%s/output-10h.json', $this->path)), $result);
        unlink($resultPath);
    }

    /**
     * @covers TableParserHandler::parseFromJsonTest()
     */
    public function testParseFromJsonTest(): void
    {
        self::assertTrue($this->handler->parseFromJsonTest(TableParserInterface::XLSX));
    }

    /**
     * @covers TableParserHandler::parseToJson()
     */
    public function testParseToJsonWithoutFile(): void
    {
        $this->expectException(TableParserHandlerException::class);
        $this->expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseToJson([]);
    }

    /**
     * @covers TableParserHandler::parseFromJson()
     */
    public function testParseFromJsonWithoutFile(): void
    {
        $this->expectException(TableParserHandlerException::class);
        $this->expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseFromJson(TableParserInterface::XLSX, []);
    }

    /**
     * @covers TableParserHandler::parseFromJson()
     */
    public function testParseFromJsonWithInvalidType(): void
    {
        $this->expectException(TableParserException::class);
        $this->expectExceptionCode(TableParserException::UNKNOWN_WRITER_TYPE);
        $this->handler->parseFromJson('Invalid', ['file_id' => sprintf('%s/output-10.json', $this->path)]);
    }

}