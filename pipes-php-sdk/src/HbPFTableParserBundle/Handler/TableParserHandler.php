<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler;

use Hanaboso\PipesPhpSdk\Parser\Exception\TableParserException;
use Hanaboso\PipesPhpSdk\Parser\TableParser;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class TableParserHandler
 *
 * @package Hanaboso\PipesPhpSdk\HbPFTableParserBundle\Handler
 */
final class TableParserHandler
{

    /**
     * TableParserHandler constructor.
     *
     * @param TableParser $tableParser
     */
    public function __construct(private readonly TableParser $tableParser)
    {
    }

    /**
     * @param mixed[] $data
     *
     * @return string
     * @throws Exception
     * @throws TableParserHandlerException
     */
    public function parseToJson(array $data): string
    {
        $fs   = new Filesystem();
        $path = $this->getFile($data, $fs);

        return $this->tableParser->parseToJson($path, $data['has_headers'] ?? FALSE);
    }

    /**
     */
    public function parseToJsonTest(): bool
    {
        return TRUE;
    }

    /**
     * @param string  $type
     * @param mixed[] $data
     *
     * @return string
     * @throws Exception
     * @throws TableParserException
     * @throws TableParserHandlerException
     */
    public function parseFromJson(string $type, array $data): string
    {
        $fs   = new Filesystem();
        $path = $this->getFile($data, $fs);

        return $this->tableParser->parseFromJson($path, $type, $data['has_headers'] ?? FALSE);
    }

    /**
     * @param string $type
     *
     * @return bool
     * @throws TableParserException
     */
    public function parseFromJsonTest(string $type): bool
    {
        $this->tableParser->createWriter(new Spreadsheet(), $type);

        return TRUE;
    }

    /**
     * @param mixed[]    $data
     * @param Filesystem $fs
     *
     * @return string
     * @throws TableParserHandlerException
     */
    private function getFile(array $data, Filesystem $fs): string
    {
        if (isset($data['file_id'])) {
            if ($fs->exists($data['file_id'])) {
                return $data['file_id'];
            }
        }

        throw new TableParserHandlerException(
            'Property not set: \'file_id\'',
            TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET,
        );
    }

}
