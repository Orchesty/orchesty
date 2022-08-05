<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\Configurator\Document\TopologyProgress;
use Hanaboso\Utils\Date\DateTimeUtils;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class TopologyProgressControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
final class TopologyProgressControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\TopologyProgressController::getProgressTopologyAction
     *
     * @throws Exception
     */
    public function testGetAllAction(): void
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
            ->setTotal(2)
            ->setStartedAt(DateTimeUtils::getUtcDateTime('2010-10-10 10:10:10'))
            ->setFinishedAt(DateTimeUtils::getUtcDateTime('2010-10-10 10:10:10')->modify('+ 10 second'));

        $this->pfd($progress);
    }

}
