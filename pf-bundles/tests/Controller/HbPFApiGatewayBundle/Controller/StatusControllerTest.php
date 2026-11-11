<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\StatusController;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class StatusControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(StatusController::class)]
final class StatusControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDashboardAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/StatusController/getStatusActionRequest.json');
    }

}
