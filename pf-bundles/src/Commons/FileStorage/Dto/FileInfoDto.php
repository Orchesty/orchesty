<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/22/17
 * Time: 4:11 PM
 */

namespace Hanaboso\PipesFramework\Commons\FileStorage\Dto;

/**
 * Class FileInfoDto
 *
 * @package Hanaboso\PipesFramework\Commons\FileStorage\Dto
 */
class FileInfoDto
{

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $size;

    /**
     * FileInfoDto constructor.
     *
     * @param string $url
     * @param string $size
     */
    function __construct(string $url, string $size)
    {
        $this->url  = $url;
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getSize(): string
    {
        return $this->size;
    }

}