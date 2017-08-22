<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 1:02 PM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\Driver;

use Doctrine\ODM\MongoDB\DocumentManager;
use Hanaboso\PipesFramework\Commons\FileStorage\PathGenerator\PathGeneratorInterface;

/**
 * Class FileStorageDriverAbstract
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Driver
 */
abstract class FileStorageDriverAbstract implements FileStorageDriverInterface
{

    /**
     * @var string
     */
    protected $filePrefix = '';

    /**
     * @var DocumentManager
     */
    protected $dm;

    /**
     * @var PathGeneratorInterface
     */
    protected $pathGenerator;


    /**
     * FileStorageDriverAbstract constructor.
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
     * @param PathGeneratorInterface $generator
     */
    public function setPathGenerator(PathGeneratorInterface $generator): void
    {
        $this->pathGenerator = $generator;
    }

    /**
     * @param string|null $filename
     *
     * @return string
     */
    protected function generatePath(?string $filename): string
    {
        return $this->filePrefix . $this->pathGenerator->generate($filename);
    }

}