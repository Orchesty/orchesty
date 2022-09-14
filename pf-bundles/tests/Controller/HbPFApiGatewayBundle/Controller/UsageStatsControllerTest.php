<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class UsageStatsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UsageStatsController
 * @covers  \Hanaboso\PipesFramework\HbPFUsageStatsBundle\Controller\UsageStatsController
 */
final class UsageStatsControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDashboardAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UsageStatsController/emitEventActionRequest.json');
    }

}
