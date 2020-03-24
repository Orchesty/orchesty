<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\TopologyInstaller\Dto;

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
     * @var string
     */
    private string $name;

    /**
     * @var string
     */
    private string $path;

    /**
     * TopologyFile constructor.
     *
     * @param string $name
     * @param string $path
     */
    public function __construct(string $name, string $path)
    {
        $this->name = $name;
        $this->path = $path;
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
            $level   = error_reporting(0);
            $content = file_get_contents($this->path);
            error_reporting($level);
            if ($content === FALSE) {
                $error = error_get_last();

                throw new RuntimeException($error['message'] ?? 'Unknown Error');
            }

            return $content;
        }

        throw new RuntimeException(sprintf('File "%s" not found!', $this->path));
    }

}
