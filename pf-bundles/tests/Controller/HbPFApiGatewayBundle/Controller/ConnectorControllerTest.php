<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class ConnectorControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ConnectorController
 */
final class ConnectorControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ConnectorController::processEvent
     *
     * @throws Exception
     */
    public function testProcessEventAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/ConnectorController/processEventRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\ConnectorController::processAction
     *
     * @throws Exception
     */
    public function testProcessActionAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/ConnectorController/processActionRequest.json');
    }

}