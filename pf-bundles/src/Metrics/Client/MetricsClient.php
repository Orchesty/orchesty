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
     * @var string
     */
    private string $host;

    /**
     * @var int
     */
    private int $port;

    /**
     * @var string
     */
    private string $user;

    /**
     * @var string
     */
    private string $password;

    /**
     * @var string
     */
    private string $database;

    /**
     * MetricsClient constructor.
     *
     * @param string $host
     * @param int    $port
     * @param string $user
     * @param string $password
     * @param string $database
     */
    public function __construct(string $host, int $port, string $user, string $password, string $database)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
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
