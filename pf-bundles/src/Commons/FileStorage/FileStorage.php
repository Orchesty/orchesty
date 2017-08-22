<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/22/17
 * Time: 9:22 AM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\FileStorage\Document\File;
use Hanaboso\PipesFramework\Commons\FileStorage\Driver\FileStorageDriverLocator;
use Hanaboso\PipesFramework\Commons\FileStorage\Dto\FileContentDto;

/**
 * Class FileStorage
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage
 */
class FileStorage
{

    /**
     * @var FileStorageDriverLocator
     */
    private $locator;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * FileStorage constructor.
     *
     * @param FileStorageDriverLocator $locator
     * @param DocumentManager          $dm
     */
    function __construct(FileStorageDriverLocator $locator, DocumentManager $dm)
    {
        $this->locator = $locator;
        $this->dm      = $dm;
    }

    /**
     * @param FileContentDto $content
     *
     * @return File
     */
    public function saveFile(FileContentDto $content): File
    {
        $driver = $this->locator->get($content->getStorageType());
        $info   = $driver->save($content->getContent(), $content->getFilename());

        $file = new File();
        $file
            ->setFilename($content->getFilename() ?? $info->getUrl())
            ->setFileFormat($content->getFormat())
            ->setFileUrl($info->getUrl())
            ->setSize($info->getSize())
            ->setStorageType($content->getStorageType());

        $this->dm->persist($file);
        $this->dm->flush($file);

        return $file;
    }

    /**
     * @param File $file
     *
     * @return FileContentDto
     */
    public function getFileContent(File $file): FileContentDto
    {
        $driver = $this->locator->get($file->getStorageType());

        return new FileContentDto($driver->get($file->getFileUrl()), $file->getFilename());
    }

    /**
     * @param File $file
     */
    public function deleteFile(File $file): void
    {
        $driver = $this->locator->get($file->getStorageType());
        $driver->delete($file->getFileUrl());

        $this->dm->remove($file);
        $this->dm->flush($file);
    }

}