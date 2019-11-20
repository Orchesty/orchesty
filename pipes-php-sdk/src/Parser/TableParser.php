<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Parser;

use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesPhpSdk\Parser\Exception\TableParserException;
use Nette\Utils\Strings;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use PhpOffice\PhpSpreadsheet\Writer\Ods;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class TableParser
 *
 * @package Hanaboso\PipesPhpSdk\Parser
 */
final class TableParser implements TableParserInterface
{

    /**
     * @param string    $path
     * @param bool|null $hasHeaders
     *
     * @return string
     * @throws Exception
     * @throws ReaderException
     */
    public function parseToJson(string $path, ?bool $hasHeaders = FALSE): string
    {
        $worksheet = IOFactory::load($path)->getActiveSheet();
        $maxRow    = $worksheet->getHighestDataRow();
        $maxColumn = Coordinate::columnIndexFromString($worksheet->getHighestDataColumn());

        $data        = [];
        $columnNames = [];
        for ($row = 1; $row <= $maxRow; $row++) {
            $columns = [];
            for ($column = 0; $column < $maxColumn; $column++) {
                if ($row === 1) {
                    if ($hasHeaders) {
                        $columnNames[] = $this->getTrimmedCellValue($worksheet, $column + 1, 1);
                    } else {
                        $columnNames[]    = $column;
                        $columns[$column] = $this->getTrimmedCellValue($worksheet, $column + 1, $row);
                    }
                } else {
                    $columns[$columnNames[$column]] = $this->getTrimmedCellValue($worksheet, $column + 1, $row);
                }
            }

            if (!empty($columns)) {
                $data[] = $columns;
            }
        }

        return Json::encode($data);
    }

    /**
     * @param string    $path
     * @param string    $type
     * @param bool|null $hasHeaders
     *
     * @return string
     * @throws Exception
     * @throws TableParserException
     * @throws WriterException
     */
    public function parseFromJson(
        string $path,
        string $type = TableParserInterface::XLSX,
        ?bool $hasHeaders = FALSE
    ): string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet   = $spreadsheet->setActiveSheetIndex(0);
        $writer      = $this->createWriter($spreadsheet, $type);
        $data        = Json::decode((string) file_get_contents($path));

        $headers = [];
        if ($hasHeaders) {
            $headers = array_keys((array) $data[0]);
            foreach ($headers as $column => $value) {
                $this->setCellValue($worksheet, ++$column, 1, $value);
            }
        }

        foreach ($data as $row => $rowData) {
            foreach ($rowData as $column => $value) {
                $hasHeaders
                    ? $this->setCellValue($worksheet, array_search($column, $headers, TRUE) + 1, $row + 2, $value)
                    : $this->setCellValue($worksheet, (int) ++$column, $row + 1, $value);
            }
        }

        $path = sprintf('/tmp/%s.%s', microtime(TRUE), $type);
        $writer->save($path);

        return (string) realpath($path);
    }

    /**
     * @param Worksheet $worksheet
     * @param int       $column
     * @param int       $row
     *
     * @return string|null
     * @throws Exception
     */
    private function getTrimmedCellValue(Worksheet $worksheet, int $column, int $row): ?string
    {
        $cell = $worksheet->getCellByColumnAndRow($column, $row);
        if ($cell) {
            return Strings::trim($cell->getCalculatedValue());
        }

        return NULL;
    }

    /**
     * @param Worksheet $worksheet
     * @param int       $column
     * @param int       $row
     * @param string    $value
     *
     * @throws Exception
     */
    private function setCellValue(Worksheet $worksheet, int $column, int $row, string $value): void
    {
        $cell = $worksheet->getCellByColumnAndRow($column, $row);
        if ($cell) {
            $cell->setValue($value);
        }
    }

    /**
     * @param Spreadsheet $spreadsheet
     * @param string      $type
     *
     * @return IWriter
     * @throws TableParserException
     */
    public function createWriter(Spreadsheet $spreadsheet, string $type): IWriter
    {
        switch ($type) {
            case TableParserInterface::XLSX:
                return new Xlsx($spreadsheet);
            case TableParserInterface::XLS:
                return new Xls($spreadsheet);
            case TableParserInterface::ODS:
                return new Ods($spreadsheet);
            case TableParserInterface::CSV:
                return new Csv($spreadsheet);
            case TableParserInterface::HTML:
                return new Html($spreadsheet);
            default:
                throw new TableParserException(
                    sprintf('Unknown writer type: \'%s\'', $type),
                    TableParserException::UNKNOWN_WRITER_TYPE
                );
        }
    }

}
