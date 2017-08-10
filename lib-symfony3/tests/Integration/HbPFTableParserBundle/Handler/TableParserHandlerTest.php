<?php declare(strict_types=1);

namespace Tests\Integration\HbPFTableParserBundle\Handler;

use Hanaboso\PipesFramework\Commons\ServiceStorage\JSONSingleFileStorage;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesFramework\Parser\TableParser;
use Hanaboso\PipesFramework\Parser\TableParserException;
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
        $this->handler = new TableParserHandler(new JSONSingleFileStorage(), new TableParser());
        $this->path    = sprintf('%s/../../Parser/data', __DIR__);
    }

    /**
     *
     */
    public function testParseToJson(): void
    {
        $result = $this->handler->parseToJson([
            'file_id'     => sprintf('%s/input-1k.xlsx', $this->path),
            'has_headers' => FALSE,
        ]);
        $this->assertEquals(file_get_contents(sprintf('%s/output-1k.json', $this->path)), $result);

        $result = $this->handler->parseToJson([
            'file_id'     => sprintf('%s/input-1kh.xlsx', $this->path),
            'has_headers' => TRUE,
        ]);
        $this->assertEquals(file_get_contents(sprintf('%s/output-1kh.json', $this->path)), $result);
    }

    /**
     *
     */
    public function testParseFromJson(): void
    {
        $resultPath = $this->handler->parseFromJson(TableParserInterface::XLSX, [
            'file_id'     => sprintf('%s/output-1k.json', $this->path),
            'has_headers' => FALSE,
        ]);
        $result     = $this->handler->parseToJson([
            'file_id'     => $resultPath,
            'has_headers' => FALSE,
        ]);
        $this->assertEquals(file_get_contents(sprintf('%s/output-1k.json', $this->path)), $result);
        unlink($resultPath);

        $resultPath = $this->handler->parseFromJson(TableParserInterface::XLSX, [
            'file_id'     => sprintf('%s/output-1kh.json', $this->path),
            'has_headers' => TRUE,
        ]);
        $result     = $this->handler->parseToJson([
            'file_id'     => $resultPath,
            'has_headers' => TRUE,
        ]);
        $this->assertEquals(file_get_contents(sprintf('%s/output-1kh.json', $this->path)), $result);
        unlink($resultPath);
    }

    /**
     *
     */
    public function testParseToJsonWithoutFile(): void
    {
        $this->expectException(TableParserHandlerException::class);
        $this->expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseToJson([]);
    }

    /**
     *
     */
    public function testParseFromJsonWithoutFile(): void
    {
        $this->expectException(TableParserHandlerException::class);
        $this->expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseFromJson(TableParserInterface::XLSX, []);
    }

    /**
     *
     */
    public function testParseFromJsonWithInvalidType(): void
    {
        $this->expectException(TableParserException::class);
        $this->expectExceptionCode(TableParserException::UNKNOWN_WRITER_TYPE);
        $this->handler->parseFromJson('Invalid', ['file_id' => sprintf('%s/output-1k.json', $this->path)]);
    }

}