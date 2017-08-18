<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\HbPFTableParserBundle\Handler;

use Hanaboso\PipesFramework\Commons\BaseService\NullServiceInterface;
use Hanaboso\PipesFramework\Commons\ServiceStorage\ServiceStorageInterface;
use Hanaboso\PipesFramework\Parser\Exception\TableParserException;
use Hanaboso\PipesFramework\Parser\TableParser;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use stdClass;

/**
 * Class TableParserHandler
 *
 * @package Hanaboso\PipesFramework\HbPFTableParserBundle\Handler
 */
class TableParserHandler
{

    /**
     * @var ServiceStorageInterface
     */
    private $storageInterface;

    /**
     * @var TableParser
     */
    private $tableParser;

    /**
     * TableParserHandler constructor.
     *
     * @param ServiceStorageInterface $serviceStorage
     * @param TableParser             $tableParser
     */
    public function __construct(ServiceStorageInterface $serviceStorage, TableParser $tableParser)
    {
        $this->storageInterface = $serviceStorage;
        $this->tableParser      = $tableParser;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    public function parseToJson(array $data): string
    {
        return $this->tableParser->parseToJson($this->getFile($data)->path, $data['has_headers'] ?? FALSE);
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
        return $this->tableParser->parseFromJson($this->getFile($data)->path, $type, $data['has_headers'] ?? FALSE);
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
     * @param array $data
     *
     * @return stdClass
     * @throws TableParserHandlerException
     */
    private function getFile(array $data): stdClass
    {
        if (isset($data['file_id'])) {
            return $this->storageInterface->getFile(new NullServiceInterface(), $data['file_id']);
        }

        throw new TableParserHandlerException(
            'Property not set: \'file_id\'',
            TableParserHandlerException::PROPERTY_FILE_ID_NOT_SET
        );
    }

}