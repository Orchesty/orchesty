<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Logs;

use Elastica\Client;
use Hanaboso\Utils\String\DsnParser;

/**
 * Class ElasticaClientFactory
 *
 * @package Hanaboso\PipesFramework\Logs
 */
final class ElasticaClientFactory
{

    /**
     * @param string $dsn
     *
     * @return Client
     */
    public static function create(string $dsn): Client
    {
        return new Client(DsnParser::parseElasticDsn($dsn));
    }

}
