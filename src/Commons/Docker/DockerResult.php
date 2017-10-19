<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: Pavel Severyn
 * Date: 11.10.17
 * Time: 8:53
 */

namespace Hanaboso\PipesFramework\Commons\Docker;

use Psr\Http\Message\StreamInterface;

/**
 * Class DockerResult
 *
 * @package Hanaboso\PipesFramework\Commons\Docker
 */
class DockerResult
{

    /**
     * @var StreamInterface
     */
    protected $result;

    /**
     * Result constructor.
     *
     * @param StreamInterface $result
     */
    public function __construct(StreamInterface $result)
    {
        $this->result = $result;
    }

    /**
     * @return StreamInterface
     */
    public function getResult(): StreamInterface
    {
        return $this->result;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->getResult()->getContents();
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->getResult()->getSize();
    }

}
