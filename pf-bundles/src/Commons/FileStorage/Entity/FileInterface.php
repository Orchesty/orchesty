<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\FileStorage\Entity;

/**
 * Interface FileInterface
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Entity
 */
interface FileInterface
{

    /**
     * @return string
     */
    public function getFilename(): string;

    /**
     * @param string $filename
     *
     * @return FileInterface
     */
    public function setFilename(string $filename): FileInterface;

    /**
     * @return string
     */
    public function getFileFormat(): string;

    /**
     * @param string $format
     *
     * @return FileInterface
     */
    public function setFileFormat(string $format): FileInterface;

    /**
     * @return string
     */
    public function getFileUrl(): string;

    /**
     * @param string $url
     *
     * @return FileInterface
     */
    public function setFileUrl(string $url): FileInterface;

    /**
     * @return string
     */
    public function getSize(): string;

    /**
     * @param string $size
     *
     * @return FileInterface
     */
    public function setSize(string $size): FileInterface;

    /**
     * @return string
     */
    public function getStorageType(): string;

    /**
     * @param string $type
     *
     * @return FileInterface
     */
    public function setStorageType(string $type): FileInterface;

}