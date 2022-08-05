<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Exception;
use Hanaboso\PipesFramework\Metrics\Client\MetricsClient;
use Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use InfluxDB\Database\RetentionPolicy;

/**
 * Trait InfluxTestTrait
 *
 * @package PipesFrameworkTests
 */
trait InfluxTestTrait
{

    /**
     * @param Topology $topology
     * @param Node     $node
     */
    abstract protected function setFakeData(Topology $topology, Node $node): void;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $client = $this->getClient()->createClient();
        $client->selectDB('test')->drop();
        $client->query('', 'CREATE DATABASE test');
        $client->selectDB('test')->create(new RetentionPolicy('5s', '1h', 1, TRUE));
        $client->selectDB('test')->create(new RetentionPolicy('1m', '1h', 1, TRUE));
        $client->selectDB('test')->create(new RetentionPolicy('30m', '1h', 1, TRUE));
        $client->selectDB('test')->create(new RetentionPolicy('4h', '4h', 1, TRUE));
    }

    /**
     * @return Topology
     * @throws Exception
     */
    private function createTopology(): Topology
    {
        $topo = new Topology();
        $topo->setName(uniqid());
        $this->dm->persist($topo);
        $this->dm->flush();

        return $topo;
    }

    /**
     * @param Topology $topology
     *
     * @return Node
     * @throws Exception
     */
    private function createNode(Topology $topology): Node
    {
        $node = new Node();
        $node
            ->setTopology($topology->getId())
            ->setName(uniqid());
        $this->dm->persist($node);
        $this->dm->flush();

        return $node;
    }

    /**
     * @return MetricsClient
     */
    private function getClient(): MetricsClient
    {
        /** @var string $host */
        $host = self::getContainer()->getParameter('influx.host');
        /** @var int $port */
        $port = self::getContainer()->getParameter('influx.api_port');
        /** @var string $user */
        $user = self::getContainer()->getParameter('influx.user');
        /** @var string $pass */
        $pass = self::getContainer()->getParameter('influx.password');

        return new MetricsClient($host, $port, $user, $pass, 'test');
    }

    /**
     * @return InfluxMetricsManager
     */
    private function getManager(): InfluxMetricsManager
    {
        /** @var string $nodeTable */
        $nodeTable = self::getContainer()->getParameter('influx.node_table');
        /** @var string $fpmTable */
        $fpmTable = self::getContainer()->getParameter('influx.monolith_table');
        /** @var string $connTable */
        $connTable = self::getContainer()->getParameter('influx.connector_table');
        /** @var string $rabbitTable */
        $rabbitTable = self::getContainer()->getParameter('influx.rabbit_table');
        /** @var string $counterTable */
        $counterTable = self::getContainer()->getParameter('influx.counter_table');
        /** @var string $consTable */
        $consTable = self::getContainer()->getParameter('influx.rabbit_consumer_table');

        return new InfluxMetricsManager(
            $this->getClient(),
            $this->dm,
            $nodeTable,
            $fpmTable,
            $rabbitTable,
            $counterTable,
            $connTable,
            $consTable,
        );
    }

}
