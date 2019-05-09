<?php declare(strict_types=1);

namespace Tests\Integration\HbPFTableParserBundle\Handler;

use Exception;
use Hanaboso\CommonsBundle\FileStorage\Dto\FileContentDto;
use Hanaboso\CommonsBundle\FileStorage\FileStorage;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandler;
use Hanaboso\PipesFramework\HbPFTableParserBundle\Handler\TableParserHandlerException;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Hanaboso\PipesFramework\Parser\TableParser;
use Hanaboso\PipesFramework\Parser\TableParserInterface;
use Tests\KernelTestCaseAbstract;

/**
 * Class TableParserHandlerTest
 *
 * @package Tests\Integration\HbPFTableParserBundle\Handler
 */
final class TableParserHandlerTest extends KernelTestCaseAbstract
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
     * @var FileStorage
     */
    private $storage;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = self::$container->get('hbpf.file_storage');
        $this->handler = new TableParserHandler(new TableParser(), $this->storage);
        $this->path    = __DIR__ . '/../../Parser/data';
    }

    /**
     * @covers TableParserHandler::parseToJson()
     * @throws Exception
     */
    public function testParseToJson(): void
    {

        self::$container->get('hbpf.database_manager_locator');

        $result = $this->handler->parseToJson([
            'file_id'     => sprintf('%s/input-10.xlsx', $this->path),
            'has_headers' => FALSE,
        ]);
        self::assertEquals(file_get_contents(sprintf('%s/output-10.json', $this->path)), $result);

        $result = $this->handler->parseToJson([
            'file_id'     => sprintf('%s/input-10h.xlsx', $this->path),
            'has_headers' => TRUE,
        ]);
        self::assertEquals(file_get_contents(sprintf('%s/output-10h.json', $this->path)), $result);
    }

    /**
     * @covers TableParserHandler::parseToJson()
     * @throws Exception
     */
    public function testParseToJsonFromContent(): void
    {
        $content = (string) file_get_contents(sprintf('%s/input-10.xlsx', $this->path));
        $file    = $this->storage->saveFileFromContent(new FileContentDto($content, 'xlsx'));

        $result = $this->handler->parseToJson(['file_id' => $file->getId()]);
        self::assertEquals(file_get_contents(sprintf('%s/output-10.json', $this->path)), $result);
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
     * @throws Exception
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
        self::assertEquals(file_get_contents(sprintf('%s/output-10.json', $this->path)), $result);
        unlink($resultPath);

        $resultPath = $this->handler->parseFromJson(TableParserInterface::XLSX, [
            'file_id'     => sprintf('%s/output-10h.json', $this->path),
            'has_headers' => TRUE,
        ]);
        $result     = $this->handler->parseToJson([
            'file_id'     => $resultPath,
            'has_headers' => TRUE,
        ]);
        self::assertEquals(file_get_contents(sprintf('%s/output-10h.json', $this->path)), $result);
        unlink($resultPath);
    }

    /**
     * @covers TableParserHandler::parseFromJson()
     * @throws Exception
     */
    public function testParseFromJsonFromContent(): void
    {
        $content = (string) file_get_contents(sprintf('%s/output-10.json', $this->path));
        $file    = $this->storage->saveFileFromContent(new FileContentDto($content, 'json'));

        $resultPath = $this->handler->parseFromJson(TableParserInterface::XLSX, [
            'file_id' => $file->getId(),
        ]);
        $result     = $this->handler->parseToJson([
            'file_id'     => $resultPath,
            'has_headers' => FALSE,
        ]);
        self::assertEquals(file_get_contents(sprintf('%s/output-10.json', $this->path)), $result);
        unlink($resultPath);
    }

    /**
     * @covers TableParserHandler::parseFromJsonTest()
     * @throws Exception
     */
    public function testParseFromJsonTest(): void
    {
        self::assertTrue($this->handler->parseFromJsonTest(TableParserInterface::XLSX));
    }

    /**
     * @covers TableParserHandler::parseToJson()
     * @throws Exception
     */
    public function testParseToJsonWithoutFile(): void
    {
        self::expectException(TableParserHandlerException::class);
        self::expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseToJson([]);
    }

    /**
     * @covers TableParserHandler::parseFromJson()
     * @throws Exception
     */
    public function testParseFromJsonWithoutFile(): void
    {
        self::expectException(TableParserHandlerException::class);
        self::expectExceptionCode(TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET);
        $this->handler->parseFromJson(TableParserInterface::XLSX, []);
    }

    /**
     * @covers TableParserHandler::parseFromJson()
     * @throws Exception
     */
    public function testParseFromJsonWithInvalidType(): void
    {
        self::expectException(TableParserException::class);
        self::expectExceptionCode(TableParserException::UNKNOWN_WRITER_TYPE);
        $this->handler->parseFromJson('Invalid', ['file_id' => sprintf('%s/output-10.json', $this->path)]);
    }

}
