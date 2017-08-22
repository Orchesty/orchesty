<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 1:44 PM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\Driver;

use Doctrine\MongoDB\GridFSFile;
use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\Exception\FileStorageException;
use Hanaboso\PipesFramework\Commons\FileStorage\PathGenerator\PathGeneratorInterface;

/**
 * Class MongoFileDriver
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Driver
 */
class MongoFileDriver
{

    /**
     * @var string
     */
    private $filePrefix = '';

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var PathGeneratorInterface
     */
    private $pathGenerator;

    /**
     * MongoFileDriver constructor.
     *
     * @param DocumentManager        $dm
     * @param PathGeneratorInterface $defaultPathGenerator
     */
    function __construct(DocumentManager $dm, PathGeneratorInterface $defaultPathGenerator)
    {
        $this->dm            = $dm;
        $this->pathGenerator = $defaultPathGenerator;
    }

    /**
     * @param string      $content
     * @param null|string $filename
     *
     * @return string
     */
    public function save(string $content, ?string $filename = NULL): string
    {
        $filename = $this->generatePath($filename);

        $gridFile = new GridFSFile();
        $gridFile->setBytes($content);

        $file = new FileMongo();
        $file
            ->setContent($gridFile)
            ->setFilename($filename);

        $this->dm->persist($file);
        $this->dm->flush($file);

        return $filename;
    }

    /**
     * @param string $fileId
     */
    public function delete(string $fileId): void
    {
        /** @var FileMongo $file */
        $file = $this->get($fileId);

        $this->dm->remove($file);
        $this->dm->flush();
    }

    /**
     * @param string $fileId
     *
     * @return mixed
     * @throws FileStorageException
     */
    public function get(string $fileId)
    {
        $file = $this->dm->getRepository(FileMongo::class)->find($fileId);

        if (!$file) {
            throw new FileStorageException(
                sprintf('File in Mongo with given id [%s] not found.', $fileId),
                FileStorageException::FILE_NOT_FOUND
            );
        }

        return $file;
    }

    /**
     * @param string|null $filename
     *
     * @return string
     */
    private function generatePath(?string $filename): string
    {
        return $this->filePrefix . $this->pathGenerator->generate($filename);
    }

}