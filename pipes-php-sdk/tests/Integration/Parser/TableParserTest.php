<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Parser;

use Exception;
use Hanaboso\PipesPhpSdk\Parser\TableParser;
use Hanaboso\PipesPhpSdk\Parser\TableParserInterface;
use Hanaboso\Utils\File\File;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class TableParserTest
 *
 * @package PipesPhpSdkTests\Integration\Parser
 */
final class TableParserTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Parser\TableParser::createWriter
     * @throws Exception
     */
    public function testCreateWriter(): void
    {
        $parser      = new TableParser();
        $spreadsheet = new Spreadsheet();

        $xls = $parser->createWriter($spreadsheet, TableParserInterface::XLS);
        self::assertInstanceOf(Xls::class, $xls);

        $ods = $parser->createWriter($spreadsheet, TableParserInterface::ODS);
        self::assertInstanceOf(Ods::class, $ods);

        $csv = $parser->createWriter($spreadsheet, TableParserInterface::CSV);
        self::assertInstanceOf(Csv::class, $csv);

        $html = $parser->createWriter($spreadsheet, TableParserInterface::HTML);
        self::assertInstanceOf(Html::class, $html);
    }

    /**
     * @covers       \Hanaboso\PipesPhpSdk\Parser\TableParser::parseToJson
     * @covers       \Hanaboso\PipesPhpSdk\Parser\TableParser::getTrimmedCellValue
     *
     * @dataProvider getParseToJsonData
     *
     * @param string $input
     * @param string $output
     * @param bool   $hasHeaders
     *
     * @throws Exception
     */
    public function testParseToJson(string $input, string $output, bool $hasHeaders): void
    {
        $parser = new TableParser();
        $result = $parser->parseToJson(__DIR__ . $input, $hasHeaders);
        self::assertEquals(File::getContent(__DIR__ . $output), $result);
    }

    /**
     * @covers       \Hanaboso\PipesPhpSdk\Parser\TableParser::parseFromJson
     * @covers       \Hanaboso\PipesPhpSdk\Parser\TableParser::setCellValue
     * @covers       \Hanaboso\PipesPhpSdk\Parser\TableParser::createWriter
     *
     * @dataProvider getParseFromJsonData
     *
     * @param string $input
     * @param string $type
     * @param bool   $hasHeaders
     *
     * @throws Exception
     */
    public function testParseFromJson(string $input, string $type, bool $hasHeaders): void
    {
        $parser = new TableParser();
        $path   = $parser->parseFromJson(__DIR__ . $input, $type, $hasHeaders);

        $result = $parser->parseToJson($path, $hasHeaders);
        self::assertEquals(File::getContent(__DIR__ . $input), $result);

        unlink($path);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\Parser\TableParser::getTrimmedCellValue
     * @throws Exception
     */
    public function testGetTrimmedCellValue(): void
    {
        $parser    = new TableParser();
        $worksheet = self::createPartialMock(Worksheet::class, ['getCellByColumnAndRow']);
        $worksheet
            ->expects(self::any())->method('getCellByColumnAndRow')
            ->willReturn(new Cell('1', '2', $worksheet));

        $value = $this->invokeMethod($parser, 'getTrimmedCellValue', [$worksheet, 500, 500]);

        self::assertEquals('1', $value);
    }

    /**
     * @return mixed[]
     */
    public function getParseToJsonData(): array
    {
        return [
            ['/data/input-10.xlsx', '/data/output-10.json', FALSE],
            ['/data/input-10.xls', '/data/output-10.json', FALSE],
            ['/data/input-10.ods', '/data/output-10.json', FALSE],
            ['/data/input-10.csv', '/data/output-10.json', FALSE],
            ['/data/input-10.html', '/data/output-10.json', FALSE],
            ['/data/input-10h.xlsx', '/data/output-10h.json', TRUE],
            ['/data/input-10h.xls', '/data/output-10h.json', TRUE],
            ['/data/input-10h.ods', '/data/output-10h.json', TRUE],
            ['/data/input-10h.csv', '/data/output-10h.json', TRUE],
            ['/data/input-10h.html', '/data/output-10h.json', TRUE],
        ];
    }

    /**
     * @return mixed[]
     */
    public function getParseFromJsonData(): array
    {
        return [
            ['/data/output-10.json', TableParserInterface::XLSX, FALSE],
            ['/data/output-10.json', TableParserInterface::XLS, FALSE],
            ['/data/output-10.json', TableParserInterface::ODS, FALSE],
            ['/data/output-10.json', TableParserInterface::CSV, FALSE],
            ['/data/output-10.json', TableParserInterface::HTML, FALSE],
            ['/data/output-10h.json', TableParserInterface::XLSX, TRUE],
            ['/data/output-10h.json', TableParserInterface::XLS, TRUE],
            ['/data/output-10h.json', TableParserInterface::ODS, TRUE],
            ['/data/output-10h.json', TableParserInterface::CSV, TRUE],
            ['/data/output-10h.json', TableParserInterface::HTML, TRUE],
        ];
    }

}
