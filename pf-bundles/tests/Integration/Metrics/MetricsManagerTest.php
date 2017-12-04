<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 15:49
 */

namespace Tests\Integration\Metrics;

use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Client\MetricsClient;
use Hanaboso\PipesFramework\Metrics\Exception\MetricsException;
use Hanaboso\PipesFramework\Metrics\MetricsManager;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
use InfluxDB\Exception;
use InfluxDB\Point;
use Tests\KernelTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class MetricsManagerTest
 *
 * @package Tests\Integration\Metrics
 */
final class MetricsManagerTest extends KernelTestCaseAbstract
{

    use PrivateTrait;

    /**
     * @throws Database\Exception
     * @throws Exception
     * @throws MetricsException
     */
    public function testGetTopologyMetrics(): void
    {
        $id   = uniqid();
        $topo = $this->createTopo($id);

        $this->setFakeData($id);

        $manager = $this->getManager();
        $result  = $manager->getTopologyMetrics($topo, []);

        self::assertTrue(is_array($result));
    }

    /**
     * @param string $id
     *
     * @return Topology
     */
    private function createTopo(string $id): Topology
    {
        $topo = new Topology();
        $topo->setName('aaa-bbb');
        $this->setProperty($topo, 'id', $id);

        return $topo;
    }

    /**
     * @return MetricsClient
     */
    private function getClient(): MetricsClient
    {
        $host = $this->container->getParameter('influx.host');
        $port = $this->container->getParameter('influx.port');
        $user = $this->container->getParameter('influx.user');
        $pass = $this->container->getParameter('influx.password');

        return new MetricsClient($host, $port, $user, $pass, 'test');
    }

    /**
     * @return MetricsManager
     */
    private function getManager(): MetricsManager
    {
        $table = $this->container->getParameter('influx.table');

        return new MetricsManager($this->getClient(), $table);
    }

    /**
     * @param string $id
     *
     * @throws Database\Exception
     * @throws MetricsException
     * @throws Exception
     */
    private function setFakeData(string $id): void
    {
        $points = [
            new Point(
                'pipes_node',
                NULL,
                [
                    MetricsManager::NODE             => 'node',
                    MetricsManager::TOPOLOGY         => $id,
                ],
                [
                    MetricsManager::TOP_PROCESS_TIME => 10,
                    MetricsManager::WAIT_TIME        => 10,
                ]
            ),
        ];

        $this->getClient()->createClient()->selectDB('test')->create(new RetentionPolicy('test', '1d', 1, TRUE));
        $database = $this->getClient()->getDatabase('test');
        $database->writePoints($points, Database::PRECISION_SECONDS);
    }

}