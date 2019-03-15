<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Client;

use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Query\Builder;

/**
 * Interface ClientInterface
 *
 * @package Hanaboso\PipesFramework\Metrics\Client
 */
interface ClientInterface
{

    /**
     * @return Builder
     */
    public function getQueryBuilder(): Builder;

    /**
     * @param null $name
     *
     * @return Database
     */
    public function getDatabase($name = NULL): Database;

    /**
     * @return Client
     */
    public function createClient(): Client;

}
