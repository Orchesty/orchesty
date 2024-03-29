<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use DateTime;
use Exception;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Model\DashboardManager;
use Hanaboso\PipesFramework\Database\Document\Node;
use Hanaboso\PipesFramework\Database\Document\Topology;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetrics;
use Hanaboso\PipesFramework\Metrics\Document\ConnectorsMetricsFields;
use Hanaboso\PipesFramework\Metrics\Document\Tags;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use PipesFrameworkTests\MongoTestTrait;

/**
 * Class DashboardControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\DashboardController
 */
final class DashboardControllerTest extends ControllerTestCaseAbstract
{

    use MongoTestTrait;

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\DashboardController::getDashboardAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\DashboardHandler::getMetrics
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardManager::getDashboardData
     * @covers \Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerAbstract::getTopologiesProcessTimeMetrics
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setTotalRuns
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setErrorsCount
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setSuccessCount
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setActiveTopologies
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setDisabledTopologies
     * @covers \Hanaboso\PipesFramework\Configurator\Model\DashboardDto::setInstalledApps
     *
     * @throws Exception
     */
    public function testDashboardAction(): void
    {
        $topology = $this->createTopology();
        $node     = $this->createNode($topology);

        $dashManager = new DashboardManager($this->dm);
        self::getContainer()->set('hbpf.configurator.manager.dashboard', $dashManager);

        $this->setFakeData($topology, $node);

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/DashboardController/getDashboardRequest.json');
    }

    /**
     * @param Topology $topology
     * @param Node     $node
     *
     * @return void
     * @throws Exception
     */
    private function setFakeData(Topology $topology, Node $node): void
    {
        $database      = self::getContainer()->get('doctrine_mongodb.odm.metrics_document_manager');
        $pipesDatabase = self::getContainer()->get('doctrine_mongodb.odm.document_manager');

        $conMetrics1 = new ConnectorsMetrics(
            new ConnectorsMetricsFields(2),
            (new Tags())
                ->setTopologyId($topology->getId())
                ->setNodeId($node->getId()),
        );
        $conMetrics2 = new ConnectorsMetrics(
            new ConnectorsMetricsFields(10),
            (new Tags())
                ->setTopologyId($topology->getId())
                ->setNodeId($node->getId()),
        );
        $database->persist($conMetrics1);
        $database->persist($conMetrics2);

        $multiCounterData  = (new TopologyProgress())
            ->setTopologyId($topology->getId())
            ->setStartedAt(new DateTime())
            ->setNok(1);
        $multiCounterData2 = (new TopologyProgress())
            ->setTopologyId($topology->getId())
            ->setStartedAt(new DateTime())
            ->setNok(1);
        $pipesDatabase->persist($multiCounterData);
        $pipesDatabase->persist($multiCounterData2);

        $database->flush();
        $pipesDatabase->flush();
    }

}
