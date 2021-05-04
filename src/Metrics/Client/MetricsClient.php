<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Metrics\Client;

use Hanaboso\PipesFramework\Metrics\Builder\Builder;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Query\Builder as InfluxDbBuilder;

/**
 * Class MetricsClient
 *
 * @package Hanaboso\PipesFramework\Metrics\Client
 */
final class MetricsClient implements ClientInterface
{

    /**
     * MetricsClient constructor.
     *
     * @param string $host
     * @param int    $port
     * @param string $user
     * @param string $password
     * @param string $database
     */
    public function __construct(
        private string $host,
        private int $port,
        private string $user,
        private string $password,
        private string $database,
    )
    {
    }

    /**
     * @return InfluxDbBuilder
     * @throws MetricsException
     */
    public function getQueryBuilder(): InfluxDbBuilder
    {
        // InfluxDb library hack
        return new Builder($this->getDatabase());
    }

    /**
     * @param string|null $name
     *
     * @return Database
     * @throws MetricsException
     */
    public function getDatabase(?string $name = NULL): Database
    {
        $client   = $this->createClient();
        $name   ??= $this->database;
        $database = $client->selectDB($name);

        if (!$database->exists()) {
            throw new MetricsException(sprintf('Database "%s" does not exist!', $name), MetricsException::DB_NOT_EXIST);
        }

        return $database;
    }

    /**
     * @return Client
     */
    public function createClient(): Client
    {
        return new Client($this->host, $this->port, $this->user, $this->password);
    }

}
