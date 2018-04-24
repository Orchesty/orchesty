<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFTableParserBundle\Handler;

use Hanaboso\CommonsBundle\FileStorage\FileStorage;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Hanaboso\PipesFramework\Parser\TableParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class TableParserHandler
 *
 * @package Hanaboso\PipesFramework\HbPFTableParserBundle\Handler
 */
class TableParserHandler
{

    /**
     * @var TableParser
     */
    private $tableParser;

    /**
     * @var FileStorage
     */
    private $fileStorage;

    /**
     * TableParserHandler constructor.
     *
     * @param TableParser $tableParser
     * @param FileStorage $fileStorage
     */
    public function __construct(TableParser $tableParser, FileStorage $fileStorage)
    {
        $this->tableParser = $tableParser;
        $this->fileStorage = $fileStorage;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function parseToJson(array $data): string
    {
        $fs     = new Filesystem();
        $is_tmp = FALSE;
        $path   = $this->getFile($data, $is_tmp, $fs);
        $res    = $this->tableParser->parseToJson($path, $data['has_headers'] ?? FALSE);

        if ($is_tmp) {
            $fs->remove($path);
        }

        return $res;
    }

    /**
     * @throws TableParserHandlerException
     */
    public function parseToJsonTest(): bool
    {
        if (!$this->tableParser) {
            throw new TableParserException(
                'Table parser not exists',
                TableParserException::PARSER_NOT_EXISTS
            );
        }

        return TRUE;
    }

    /**
     * @param string $type
     * @param array  $data
     *
     * @return string
     */
    public function parseFromJson(string $type, array $data): string
    {
        $fs     = new Filesystem();
        $is_tmp = FALSE;
        $path   = $this->getFile($data, $is_tmp, $fs);
        $res    = $this->tableParser->parseFromJson($path, $type, $data['has_headers'] ?? FALSE);

        if ($is_tmp) {
            $fs->remove($path);
        }

        return $res;
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
     * @param array           $data
     * @param bool            $is_tmp
     * @param Filesystem|null $fs
     *
     * @return string
     * @throws TableParserHandlerException
     */
    private function getFile(array $data, bool &$is_tmp, ?Filesystem $fs = NULL): string
    {
        if (isset($data['file_id'])) {
            if (!$fs) {
                $fs = new Filesystem();
            }

            if ($fs->exists($data['file_id'])) {
                $is_tmp = FALSE;

                return $data['file_id'];
            }

            $is_tmp = TRUE;
            $file   = $this->fileStorage->getFileDocument($data['file_id']);
            $file   = $this->fileStorage->getFileStorage($file)->getContent();
            $path   = __DIR__ . '/' . uniqid();
            $fs->dumpFile($path, $file);

            return $path;
        }

        throw new TableParserHandlerException(
            'Property not set: \'file_id\'',
            TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET
        );
    }

}