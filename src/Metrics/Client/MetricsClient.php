<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 11:45
 */

namespace Hanaboso\PipesFramework\Metrics\Client;

use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Query\Builder;

/**
 * Class MetricsClient
 *
 * @package Hanaboso\PipesFramework\Metrics\Client
 */
class MetricsClient implements ClientInterface
{

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $database;

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
     * @return Builder
     * @throws MetricsException
     */
    public function getQueryBuilder(): Builder
    {
        return $this->getDatabase()->getQueryBuilder();
    }

    /**
     *
     * @param null $name
     *
     * @return Database
     * @throws MetricsException
     */
    public function getDatabase($name = NULL): Database
    {
        $client   = $this->createClient();
        $name     = $name ?? $this->database;
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