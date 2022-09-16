<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Model\DashboardManager;
use Hanaboso\PipesFramework\Metrics\Manager\InfluxMetricsManager;
use Hanaboso\PipesFramework\Metrics\Manager\MetricsManagerLoader;
use Hanaboso\PipesPhpSdk\Database\Document\Node;
use Hanaboso\PipesPhpSdk\Database\Document\Topology;
use InfluxDB\Database;
use InfluxDB\Point;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use PipesFrameworkTests\InfluxTestTrait;

/**
 * Class DashboardControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\DashboardController
 */
final class DashboardControllerTest extends ControllerTestCaseAbstract
{

    use InfluxTestTrait;

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\DashboardController::getDashboardAction
     *
     * @throws Exception
     */
    public function testDashboardAction(): void
    {
        $topology = $this->createTopology();
        $node     = $this->createNode($topology);

        $man = self::createPartialMock(MetricsManagerLoader::class, ['getManager']);
        $man->method('getManager')->willReturn($this->getManager());
        self::getContainer()->set('hbpf.metrics.manager_loader', $man);

        $dashManager = new DashboardManager($man, $this->dm);
        self::getContainer()->set('hbpf.configurator.manager.dashboard', $dashManager);

        $this->setFakeData($topology, $node);

        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/DashboardController/getDashboardRequest.json');
    }

    /**
     * @param Topology    $topology
     * @param Node        $node
     * @param string|null $key
     * @param string|null $user
     *
     * @throws Exception
     */
    private function setFakeData(Topology $topology, Node $node, ?string $key = NULL, ?string $user = NULL): void
    {
        $database = $this->getClient()->getDatabase('test');

        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::TOPOLOGY => $topology->getId(),
                    InfluxMetricsManager::NODE     => $node->getId(),
                ],
                [
                    InfluxMetricsManager::MAX_TIME => 10,
                    InfluxMetricsManager::MIN_TIME => 2,
                    InfluxMetricsManager::AVG_TIME => 6,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);

        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '123',
                ],
                [
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);

        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '123',
                ],
                [
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);

        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '123',
                ],
                [
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);

        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '123',
                ],
                [
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);

        usleep(10);
        $points = [
            new Point(
                'connectors',
                NULL,
                [
                    InfluxMetricsManager::USER        => $user,
                    InfluxMetricsManager::APPLICATION => $key,
                    InfluxMetricsManager::CORRELATION => '123',
                ],
                [
                    InfluxMetricsManager::APP_COUNT  => 1,
                    InfluxMetricsManager::USER_COUNT => 1,
                ],
            ),
        ];

        $database->writePoints($points, Database::PRECISION_NANOSECONDS);
    }

}
