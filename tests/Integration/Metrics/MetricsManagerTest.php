<?php
/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 30.11.17
 * Time: 15:49
 */

namespace Tests\Integration\Metrics;

use Hanaboso\PipesFramework\Configurator\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Client\MetricsClient;
use Hanaboso\PipesFramework\Metrics\MetricsManager;
use InfluxDB\Database;
use InfluxDB\Database\RetentionPolicy;
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

    public function testGetTopologyMetrics()
    {
        $id   = uniqid();
        $topo = $this->createTopo($id);

        $this->setFakeData($id);

        $manager = $this->getManager();
        $aa      = $manager->getTopologyMetrics($topo, []);
    }

    private function createTopo(string $id)
    {
        $topo = new Topology();
        $topo->setName('aaa-bbb');
        $this->setProperty($topo, 'id', $id);

        return $topo;
    }

    private function getClient()
    {
        $host = $this->container->getParameter('influx.host');
        $port = $this->container->getParameter('influx.port');
        $user = $this->container->getParameter('influx.user');
        $pass = $this->container->getParameter('influx.password');

        return new MetricsClient($host, $port, $user, $pass, 'test');
    }

    private function getManager(): MetricsManager
    {
        $table = $this->container->getParameter('influx.table');

        return new MetricsManager($this->getClient(), $table);
    }

    private function setFakeData(string $id)
    {
        $points = [
            new Point(
                'pipes_node',
                NULL,
                [
                    MetricsManager::TOP_PROCESS_TIME => 10,
                    MetricsManager::WAIT_TIME        => 10,
                    MetricsManager::NODE             => 'node',
                    MetricsManager::TOPOLOGY         => $id,
                ]
            ),
        ];

        $this->getClient()->createClient()->selectDB('test')->create(new RetentionPolicy('test', '1d', 1, TRUE));
        $database = $this->getClient()->getDatabase('test');
        // we are writing unix timestamps, which have a second precision
        $result = $database->writePoints($points, Database::PRECISION_SECONDS);
    }

}