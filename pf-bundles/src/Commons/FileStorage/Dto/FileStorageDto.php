<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/23/17
 * Time: 8:29 AM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\Dto;

use Hanaboso\PipesFramework\Commons\FileStorage\Document\File;

/**
 * Class FileStorageDto
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Dto
 */
class FileStorageDto
{

    /**
     * @var string
     */
    private $content;

    /**
     * @var File
     */
    private $file;

    /**
     * FileStorageDto constructor.
     *
     * @param File   $file
     * @param string $content
     */
    function __construct(File $file, string $content)
    {
        $this->file    = $file;
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

}