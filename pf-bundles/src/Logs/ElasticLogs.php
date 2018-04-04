<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: venca
 * Date: 3/20/18
 * Time: 9:00 AM
 */

namespace Hanaboso\PipesFramework\Logs;

/**
 * Class ElasticLogs
 *
 * @package Hanaboso\PipesFramework\Logs
 */
class ElasticLogs implements LogsInterface
{

    /**
     * @param string $limit
     * @param string $offset
     *
     * @return array
     */
    public function getData(string $limit, string $offset): array
    {
        return [
            'limit'  => $limit,
            'offset' => $offset,
            'count'  => "0",
            'total'  => "0",
            'items'  => [],
        ];
    }

}