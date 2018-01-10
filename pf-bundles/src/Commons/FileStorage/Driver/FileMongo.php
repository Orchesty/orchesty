<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 10:29 AM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\Driver;

use Doctrine\MongoDB\GridFSFile;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Hanaboso\PipesFramework\Commons\Traits\Document\IdTrait;

/**
 * Class FileMongo
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Driver
 *
 * @ODM\Document()
 */
class FileMongo
{

    use IdTrait;

    /**
     * @var GridFSFile
     *
     * @ODM\File
     */
    private $content;

    /**
     * @var string
     *
     * @ODM\Field(type="string")
     */
    private $filename;

    /**
     * @return GridFSFile
     */
    public function getContent(): GridFSFile
    {
        return $this->content;
    }

    /**
     * @param GridFSFile $file
     *
     * @return FileMongo
     */
    public function setContent($file): FileMongo
    {
        $this->content = $file;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return FileMongo
     */
    public function setFilename(string $filename): FileMongo
    {
        $this->filename = $filename;

        return $this;
    }

}