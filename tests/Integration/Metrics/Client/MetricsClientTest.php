<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Metrics\Client;

use Hanaboso\PipesFramework\Metrics\Client\MetricsClient;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
use PipesFrameworkTests\KernelTestCaseAbstract;

/**
 * Class MetricsClientTest
 *
 * @package PipesFrameworkTests\Integration\Metrics\Client
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

        self::assertNotEmpty($client);
    }

    /**
     * @throws MetricsException
     * @throws Database\Exception
     */
    public function testGetDatabase(): void
    {
        $manager = $this->getMetricsClient();
        $manager->createClient()->selectDB('pipes')->create(new RetentionPolicy('test', '1d', 1, TRUE));
        $database = $manager->getDatabase();

        $database->create(new RetentionPolicy('test', '1d', 1, TRUE));

        self::expectException(MetricsException::class);
        self::expectExceptionCode(MetricsException::DB_NOT_EXIST);
        $manager->getDatabase('customDb');
    }

    /**
     * @throws MetricsException
     * @throws Database\Exception
     */
    public function testGetQueryBuilder(): void
    {
        $manager = $this->getMetricsClient();
        $manager->createClient()->selectDB('pipes')->create(new RetentionPolicy('test', '1d', 1, TRUE));
        $qb = $manager->getQueryBuilder();

        self::assertNotEmpty($qb);
    }

    /**
     * ------------------------------------------ HELPERS -------------------------------------
     */

    /**
     * @return MetricsClient
     */
    private function getMetricsClient(): MetricsClient
    {
        $host = self::$container->getParameter('influx.host');
        $port = self::$container->getParameter('influx.api_port');
        $user = self::$container->getParameter('influx.user');
        $pass = self::$container->getParameter('influx.password');
        $db   = self::$container->getParameter('influx.database');

        return new MetricsClient($host, $port, $user, $pass, $db);
    }

}
