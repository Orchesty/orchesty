<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 12:27
 */

namespace Tests\Integration\Metrics\Client;

use Hanaboso\PipesFramework\Metrics\Client\MetricsClient;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
use InfluxDB\Query\Builder;
use Tests\KernelTestCaseAbstract;

/**
 * Class MetricsClientTest
 *
 * @package Tests\Integration\Metrics\Client
 */
final class MetricsClientTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testCreateClient(): void
    {
        $manager = $this->getMetricsClient();
        $client  = $manager->createClient();

        self::assertInstanceOf(Client::class, $client);
    }

    /**
     *
     */
    public function testGetDatabase(): void
    {
        $manager  = $this->getMetricsClient();
        $database = $manager->getDatabase();

        $database->create(new RetentionPolicy('test', '1d', 1, TRUE));

        self::assertInstanceOf(Database::class, $database);

        $this->expectException(MetricsException::class);
        $this->expectExceptionCode(MetricsException::DB_NOT_EXIST);
        $manager->getDatabase('customDb');
    }

    /**
     *
     */
    public function testGetQueryBuilder(): void
    {
        $manager = $this->getMetricsClient();
        $qb      = $manager->getQueryBuilder();

        self::assertInstanceOf(Builder::class, $qb);
    }

    /**
     * ------------------------------------------ HELPERS -------------------------------------
     */

    /**
     * @return MetricsClient
     */
    private function getMetricsClient(): MetricsClient
    {
        $host = $this->container->getParameter('influx.host');
        $port = $this->container->getParameter('influx.port');
        $user = $this->container->getParameter('influx.user');
        $pass = $this->container->getParameter('influx.password');
        $db   = $this->container->getParameter('influx.database');

        return new MetricsClient($host, $port, $user, $pass, $db);
    }

}