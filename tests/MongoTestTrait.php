<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;
use Hanaboso\PipesFramework\Metrics\Manager\MongoMetricsManager;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;

/**
 * Trait MongoTestTrait
 *
 * @package PipesFrameworkTests
 */
trait MongoTestTrait
{

    /**
     * @param Topology $topology
     * @param Node     $node
     */
    abstract protected function setFakeData(Topology $topology, Node $node): void;

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
     * @return MongoMetricsManager
     */
    private function getManager(): MongoMetricsManager
    {
        /** @var string $nodeTable */
        $nodeTable = self::getContainer()->getParameter('mongodb.node_table');
        /** @var string $fpmTable */
        $fpmTable = self::getContainer()->getParameter('mongodb.monolith_table');
        /** @var string $connTable */
        $connTable = self::getContainer()->getParameter('mongodb.connector_table');
        /** @var string $rabbitTable */
        $rabbitTable = self::getContainer()->getParameter('mongodb.rabbit_table');
        /** @var string $counterTable */
        $counterTable = self::getContainer()->getParameter('mongodb.counter_table');
        /** @var string $counterTable */
        $consumerTable = self::getContainer()->getParameter('mongodb.rabbit_consumer_table');
        /** @var DocumentManager $metricsDm */
        $metricsDm = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');

        return new MongoMetricsManager(
            $this->dm,
            $nodeTable,
            $fpmTable,
            $rabbitTable,
            $counterTable,
            $connTable,
            $metricsDm,
            5,
            $consumerTable,
        );
    }

}
