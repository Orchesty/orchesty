<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\PipesFramework\Configurator\Model\ProgressManager;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyProgressController;
use Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyProgressHandler;
use Hanaboso\Utils\Date\DateTimeUtils;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class TopologyProgressControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 */
#[CoversClass(TopologyProgressController::class)]
#[CoversClass(TopologyProgressHandler::class)]
#[CoversClass(ProgressManager::class)]
final class TopologyProgressControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetTopologyProgressAction(): void
    {
        $this->createProgress();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyProgressController/getProgressTopologyRequest.json',
            [
                'correlationId' => 'corr-id-1234',
            ],
            [':topologyId' => '123456789'],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAllAction(): void
    {
        $this->createProgress();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyProgressController/getProgressesRequest.json',
            [
                'correlationId' => 'corr-id-1234',
            ],
        );
    }

    /**
     * ---------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @throws Exception
     */
    private function createProgress(): void
    {
        $progress = new TopologyProgress();
        $progress
            ->setTopologyId('123456789')
            ->setTotal(10)
            ->setProcessedCount(5)
            ->setOk(5)
            ->setStartedAt(DateTimeUtils::getUtcDateTime('2022-06-14T09:04:58.789Z'))
            ->setFinishedAt(DateTimeUtils::getUtcDateTime('2022-06-14T09:04:59.707Z')->modify('+ 10 second'));

        $this->pfd($progress);
    }

}
