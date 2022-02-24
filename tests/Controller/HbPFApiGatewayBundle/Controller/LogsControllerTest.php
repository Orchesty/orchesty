<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Logs\Document\Logs;
use Hanaboso\PipesFramework\Logs\Document\Pipes;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class LogsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LogsController
 */
final class LogsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LogsController::topologyMetricsAction
     *
     * @throws Exception
     */
    public function testTopologyMetricsAction(): void
    {
        $pipes = new Pipes();
        $this->setProperty($pipes, 'level', 'error');
        $this->setProperty($pipes, 'correlationId', 'someId');
        $logs = new Logs();
        $this->setProperty($logs, 'pipes', $pipes);
        $this->pfd($logs);

        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/LogsController/topologyMetricsRequest.json',
            ['id' => '123456789'],
        );
    }

}
