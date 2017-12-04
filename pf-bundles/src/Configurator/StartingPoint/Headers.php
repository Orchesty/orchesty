<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 9/6/17
 * Time: 3:44 PM
 */

namespace Hanaboso\PipesFramework\Configurator\StartingPoint;

/**
 * Class Headers
 *
 * @package Hanaboso\PipesFramework\Configurator\StartingPoint
 */
class Headers
{

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return Headers
     */
    public function addHeader(string $key, $value): Headers
    {
        $this->headers[$key] = $value;

        return $this;
    }

}