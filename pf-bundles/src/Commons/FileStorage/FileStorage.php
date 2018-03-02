<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/22/17
 * Time: 9:22 AM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Hanaboso\PipesFramework\Commons\DatabaseManager\DatabaseManagerLocator;
use Hanaboso\PipesFramework\Commons\Exception\FileStorageException;
use Hanaboso\PipesFramework\Commons\FileStorage\Driver\FileStorageDriverLocator;
use Hanaboso\PipesFramework\Commons\FileStorage\Dto\FileContentDto;
use Hanaboso\PipesFramework\Commons\FileStorage\Dto\FileStorageDto;
use Hanaboso\PipesFramework\Commons\FileStorage\Entity\FileInterface;

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
     * @var EntityManager|DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $fileNamespace;

    /**
     * FileStorage constructor.
     *
     * @param FileStorageDriverLocator $locator
     * @param DatabaseManagerLocator   $dm
     * @param string                   $fileNamespace
     */
    function __construct(FileStorageDriverLocator $locator, DatabaseManagerLocator $dm, string $fileNamespace)
    {
        $this->locator       = $locator;
        $this->dm            = $dm->get();
        $this->fileNamespace = $fileNamespace;
    }

    /**
     * @param FileContentDto $content
     *
     * @return FileInterface
     * @throws FileStorageException
     */
    public function saveFileFromContent(FileContentDto $content): FileInterface
    {
        $driver = $this->locator->get($content->getStorageType());
        $info   = $driver->save($content->getContent(), $content->getFilename());

        /** @var FileInterface $file */
        $file = new $this->fileNamespace();
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
     * @param FileInterface $file
     *
     * @return FileStorageDto
     * @throws FileStorageException
     */
    public function getFileStorage(FileInterface $file): FileStorageDto
    {
        $driver = $this->locator->get($file->getStorageType());

        return new FileStorageDto($file, $driver->get($file->getFileUrl()));
    }

    /**
     * @param FileInterface $file
     *
     * @throws FileStorageException
     */
    public function deleteFile(FileInterface $file): void
    {
        $driver = $this->locator->get($file->getStorageType());
        $driver->delete($file->getFileUrl());

        $this->dm->remove($file);
        $this->dm->flush($file);
    }

    /**
     * @param string $fileId
     *
     * @return FileInterface
     * @throws FileStorageException
     */
    public function getFileDocument(string $fileId): FileInterface
    {
        /** @var FileInterface $file */
        $file = $this->dm->getRepository($this->fileNamespace)->find($fileId);

        if (!$file) {
            throw new FileStorageException(
                sprintf('File with given id [%s] was not found.', $fileId),
                FileStorageException::FILE_NOT_FOUND
            );
        }

        return $file;
    }

}