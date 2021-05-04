<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\NodeProgress;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class TopologyProgressControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFConfiguratorBundle\Controller
 */
final class TopologyProgressControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyProgressController::__construct
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Controller\TopologyProgressController::getProgressTopologyAction
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyProgressHandler::__construct
     * @covers \Hanaboso\PipesFramework\HbPFConfiguratorBundle\Handler\TopologyProgressHandler::getProgress
     * @covers \Hanaboso\PipesFramework\Configurator\Model\ProgressManager::__construct
     * @covers \Hanaboso\PipesFramework\Configurator\Model\ProgressManager::getProgress
     *
     * @throws Exception
     */
    public function testGetAllAction(): void
    {
        $this->createProgress();
        $this->assertResponse(
            __DIR__ . '/data/TopologyProgressController/getProgressTopologyRequest.json',
            [],
            [':topologyId' => '123456789'],
        );
    }

    /**
     * ---------------------------------------- HELPERS ------------------------------------
     */

    /**
     * @return TopologyProgress
     * @throws Exception
     */
    private function createProgress(): TopologyProgress
    {
        $progress = new TopologyProgress();
        $progress
            ->setTopologyId('123456789')
            ->setTopologyName('name')
            ->setCorrelationId('corr-id-1234')
            ->setStatus('OK')
            ->setFollowers(2)
            ->setStartedAt(DateTimeUtils::getUtcDateTime('2010-10-10 10:10:10'))
            ->setFinishedAt(DateTimeUtils::getUtcDateTime('2010-10-10 10:10:10')->modify('+ 10 second'))
            ->setDuration(10_000)
            ->addNode(
                (new NodeProgress())
                    ->setNodeId('12345')
                    ->setNodeName('node-name')
                    ->setStatus('OK')
                    ->setProcessId('proc-id-1234'),
            );

        $this->pfd($progress);

        return $progress;
    }

}
