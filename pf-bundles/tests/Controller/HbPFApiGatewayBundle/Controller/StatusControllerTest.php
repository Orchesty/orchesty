<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class StatusControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\StatusController
 */
final class StatusControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\StatusController::getStatusAction
     *
     * @throws Exception
     */
    public function testDashboardAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/StatusController/getStatusActionRequest.json');
    }

}
