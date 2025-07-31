<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Parser;

use Exception;
use Hanaboso\PipesPhpSdk\Parser\TableParser;
use Hanaboso\PipesPhpSdk\Parser\TableParserInterface;
use Hanaboso\Utils\File\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class TableParserTest
 *
 * @package PipesPhpSdkTests\Integration\Parser
 */
#[CoversClass(TableParser::class)]
final class TableParserTest extends KernelTestCaseAbstract
{

    /**
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
     * @param string $input
     * @param string $output
     * @param bool   $hasHeaders
     *
     * @throws Exception
     */
    #[DataProvider('getParseToJsonData')]
    public function testParseToJson(string $input, string $output, bool $hasHeaders): void
    {
        $parser = new TableParser();
        $result = $parser->parseToJson(__DIR__ . $input, $hasHeaders);
        self::assertSame(File::getContent(__DIR__ . $output), $result);
    }

    /**
     * @param string $input
     * @param string $type
     * @param bool   $hasHeaders
     *
     * @throws Exception
     */
    #[DataProvider('getParseFromJsonData')]
    public function testParseFromJson(string $input, string $type, bool $hasHeaders): void
    {
        $parser = new TableParser();
        $path   = $parser->parseFromJson(__DIR__ . $input, $type, $hasHeaders);

        $result = $parser->parseToJson($path, $hasHeaders);
        self::assertSame(File::getContent(__DIR__ . $input), $result);

        unlink($path);
    }

    /**
     * @return mixed[]
     */
    public static function getParseToJsonData(): array
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
    public static function getParseFromJsonData(): array
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
