<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class UserControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController
 */
final class UserControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::loginAction
     */
    public function testLoginAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/loginRequest.json', ['id' => '123456789']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::logoutAction
     */
    public function testLogoutAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/logoutRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::registerAction
     */
    public function testRegisterAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/registerRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::activateAction
     */
    public function testActivateAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/activateRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::setPasswordAction
     */
    public function testSetPasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/setPasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::changePasswordAction
     */
    public function testChangePasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/changePasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::resetPasswordAction
     */
    public function testResetPasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/resetPasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::deleteAction
     */
    public function testDeleteAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/deleteRequest.json');
    }

}
