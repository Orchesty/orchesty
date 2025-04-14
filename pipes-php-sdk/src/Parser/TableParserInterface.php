<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Parser;

/**
 * Interface TableParserInterface
 *
 * @package Hanaboso\PipesPhpSdk\Parser
 */
interface TableParserInterface
{

    public const string XLSX = 'xlsx';
    public const string XLS  = 'xls';
    public const string ODS  = 'ods';
    public const string CSV  = 'csv';
    public const string HTML = 'html';

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
    public function parseFromJson(string $path, string $type = self::XLSX, ?bool $hasHeaders = FALSE): string;

}
