<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/22/17
 * Time: 9:31 AM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\Dto;

use Hanaboso\PipesFramework\Commons\Enum\FileFormatEnum;
use Hanaboso\PipesFramework\Commons\Enum\StorageTypeEnum;
use Hanaboso\PipesFramework\Commons\Exception\FileStorageException;

/**
 * Class FileContentDto
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Dto
 */
class FileContentDto
{

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $content;

    /**
     * @var string|null
     */
    private $filename;

    /**
     * @var string
     */
    private $format;

    /**
     * FileContentDto constructor.
     *
     * @param string      $content
     * @param string      $format
     * @param string|null $filename
     */
    function __construct(string $content, string $format, ?string $filename = NULL)
    {
        $this->content  = $content;
        $this->filename = $filename;
        $this->type     = StorageTypeEnum::PERSISTENT;
        $this->format   = $format;
    }

    /**
     * @return string|null
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return FileContentDto
     */
    public function setFilename(string $filename): FileContentDto
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getStorageType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return FileContentDto
     * @throws FileStorageException
     */
    public function setStorageType(string $type): FileContentDto
    {
        if (StorageTypeEnum::isValid($type)) {
            $this->type = $type;
        } else {
            throw new FileStorageException(
                sprintf('Given storage type [%s] is not a valid option.', $type),
                FileStorageException::INVALID_STORAGE_TYPE
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return FileContentDto
     */
    public function setContent(string $content): FileContentDto
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * @param string $format
     *
     * @return FileContentDto
     * @throws FileStorageException
     */
    public function setFormat($format): FileContentDto
    {
        if (!FileFormatEnum::isValid($format)) {
            throw new FileStorageException(
                sprintf('Given file format [%s] is not a valid option.', $format),
                FileStorageException::INVALID_FILE_FORMAT
            );
        }
        $this->format = $format;

        return $this;
    }

}