<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Parser;

/**
 * Interface TableParserInterface
 *
 * @package Hanaboso\PipesFramework\Parser
 */
interface TableParserInterface
{

    public const XLSX = 'xlsx';
    public const XLS  = 'xls';
    public const ODS  = 'ods';
    public const CSV  = 'csv';
    public const HTML = 'html';

    /**
     * @param string    $path
     * @param bool|null $hasHeaders
     *
     * @return string
     */
    public function parseToJson(string $path, ?bool $hasHeaders = FALSE): string;

    /**
     * @param string    $path
     * @param string    $type
     * @param bool|null $hasHeaders
     *
     * @return string
     */
    public function parseFromJson(string $path,
                                  string $type = TableParserInterface::XLSX,
                                  ?bool $hasHeaders = FALSE): string;

}