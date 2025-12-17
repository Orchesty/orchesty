<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\LogsController;
use Hanaboso\PipesFramework\Logs\Document\Logs;
use Hanaboso\PipesFramework\Logs\Document\Pipes;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class LogsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(LogsController::class)]
final class LogsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testTopologyMetricsAction(): void
    {
        $pipes = new Pipes();
        $this->setProperty($pipes, 'severity', 'error');
        $this->setProperty($pipes, 'correlationId', 'someId');
        $this->setProperty($pipes, 'timestamp', 12_345);
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
