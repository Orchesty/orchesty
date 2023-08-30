<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller\Dto;

use Hanaboso\Utils\File\File;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class TopologyFile
 *
 * @package Hanaboso\PipesFramework\TopologyInstaller\Dto
 */
final class TopologyFile
{

    /**
     * TopologyFile constructor.
     *
     * @param string $name
     * @param string $path
     */
    public function __construct(private string $name, private string $path)
    {
    }

    /**
     * @param SplFileInfo $file
     *
     * @return TopologyFile
     */
    public static function from(SplFileInfo $file): TopologyFile
    {
        return new self($file->getFilename(), $file->getPathname());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param bool $withoutFileName
     *
     * @return string
     */
    public function getPath(bool $withoutFileName = FALSE): string
    {
        if ($withoutFileName) {
            return str_replace($this->name, '', $this->path);
        }

        return $this->path;
    }

    /**
     * @return string
     *
     * @throws RuntimeException
     */
    public function getFileContents(): string
    {
        if (file_exists($this->path)) {
            return File::getContent($this->path);
        }

        throw new RuntimeException(sprintf('File "%s" not found!', $this->path));
    }

}
