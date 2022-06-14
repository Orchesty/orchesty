<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class TopologyProgressControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyProgressController
 * @covers  \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyProgressHandler
 * @covers  \Hanaboso\PipesFramework\Configurator\Model\ProgressManager
 */
final class TopologyProgressControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyProgressController::getProgressTopologyAction
     *
     * @throws Exception
     */
    public function testGetAllAction(): void
    {
        $this->createProgress();
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/TopologyProgressController/getProgressTopologyRequest.json',
            [],
            [':topologyId' => '123456789'],
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
            ->setCorrelationId('corr-id-1234')
            ->setTotal(10)
            ->setOk(5)
            ->setStartedAt(DateTimeUtils::getUtcDateTime('2022-06-14T09:04:58.789Z'))
            ->setFinishedAt(DateTimeUtils::getUtcDateTime('2022-06-14T09:04:59.707Z')->modify('+ 10 second'));

        $this->pfd($progress);
    }

}
