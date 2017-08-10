<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Commons\Source;

/**
 * Interface SourceServiceInterface
 *
 * @package Hanaboso\PipesFramework\Commons\Source
 */
interface SourceServiceInterface
{

    /**
     * @param string $id
     * @param mixed  $data
     *
     * @return mixed
     */
    public function receiveData(string $id, $data);

}