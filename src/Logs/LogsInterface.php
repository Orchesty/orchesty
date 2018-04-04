<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/20/18
 * Time: 8:59 AM
 */

namespace Hanaboso\PipesFramework\Logs;

/**
 * Interface LogsInterface
 *
 * @package Hanaboso\PipesFramework\Logs
 */
interface LogsInterface
{

    /**
     * @param string $limit
     * @param string $offset
     *
     * @return array
     */
    public function getData(string $limit, string $offset): array;

}