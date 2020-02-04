<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class NotificationControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NotificationController
 */
final class NotificationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NotificationController::getSettingsAction
     */
    public function testGetSettingsAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/NotificationController/getSettingsRequest.json',
            ['id' => '123456789', 'created' => '2010-10-10 10:10:10', 'updated' => '2010-10-10 10:10:10']
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NotificationController::getSettingEventsAction
     */
    public function testGetSettingEventsAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/NotificationController/getSettingEventsRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NotificationController::getSettingAction
     *
     * @throws Exception
     */
    public function testGetSettingAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/NotificationController/getSettingRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\NotificationController::updateSettingsAction
     *
     * @throws Exception
     */
    public function testUpdateSettingsAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/NotificationController/updateSettingsRequest.json');
    }

}
