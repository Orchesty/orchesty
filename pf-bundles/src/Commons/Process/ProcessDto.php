<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: david.horacek
 * Date: 8/18/17
 * Time: 3:24 PM
 */

namespace Hanaboso\PipesFramework\Commons\Process;

/**
 * Class ProcessDto
 *
 * @package Hanaboso\PipesFramework\Commons\Process
 */

use Hanaboso\PipesFramework\Commons\Utils\PipesHeaders;

/**
 * Class ProcessDto
 *
 * @package Hanaboso\PipesFramework\Commons\Process
 */
final class ProcessDto
{

    /**
     * @var string
     */
    private $data;

    /**
     * @var array
     */
    private $headers;

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @param string $data
     *
     * @return ProcessDto
     */
    public function setData(string $data): ProcessDto
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return ProcessDto
     */
    public function setHeaders(array $headers): ProcessDto
    {
        $this->headers = PipesHeaders::clear($headers);

        return $this;
    }

    /**
     * @param string      $key
     * @param null|string $default
     *
     * @return null|string
     */
    public function getHeader(string $key, ?string $default = NULL): ?string
    {
        return $this->headers[$key] ?? $default;
    }

}