<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Parser;

use Exception;
use Hanaboso\PipesPhpSdk\Parser\TableParser;
use Hanaboso\PipesPhpSdk\Parser\TableParserInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class TableParserTest
 *
 * @package PipesPhpSdkTests\Integration\Parser
 */
final class TableParserTest extends TestCase
{

    /**
     * @param string $input
     * @param string $output
     * @param bool   $hasHeaders
     *
     * @dataProvider getParseToJsonData
     * @throws Exception
     */
    public function testParseToJson(string $input, string $output, bool $hasHeaders): void
    {
        $parser = new TableParser();
        $result = $parser->parseToJson(__DIR__ . $input, $hasHeaders);
        self::assertEquals(file_get_contents(__DIR__ . $output), $result);
    }

    /**
     * @param string $input
     * @param string $type
     * @param bool   $hasHeaders
     *
     * @dataProvider getParseFromJsonData
     * @throws Exception
     */
    public function testParseFromJson(string $input, string $type, bool $hasHeaders): void
    {
        $parser = new TableParser();
        $path   = $parser->parseFromJson(__DIR__ . $input, $type, $hasHeaders);

        $result = $parser->parseToJson($path, $hasHeaders);
        self::assertEquals(file_get_contents(__DIR__ . $input), $result);

        unlink($path);
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
