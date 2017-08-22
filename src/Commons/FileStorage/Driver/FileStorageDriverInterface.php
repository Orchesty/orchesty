<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/21/17
 * Time: 1:32 PM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\Driver;

/**
 * Class FileStorageDriverInterface
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Driver
 */
interface FileStorageDriverInterface
{

    /**
     * @param mixed       $content
     * @param null|string $filename
     *
     * @return string
     */
    public function save($content, ?string $filename = NULL): string;

    /**
     * @param string $filename
     */
    public function delete(string $filename): void;

    /**
     * @param string $filename
     *
     * @return mixed
     */
    public function get(string $filename);

}