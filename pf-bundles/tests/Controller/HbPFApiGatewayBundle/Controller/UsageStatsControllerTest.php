<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UsageStatsController;
use Hanaboso\PipesFramework\HbPFUsageStatsBundle\Controller\UsageStatsController as UsageStatsControllerBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class UsageStatsControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(UsageStatsController::class)]
#[CoversClass(UsageStatsControllerBase::class)]
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
