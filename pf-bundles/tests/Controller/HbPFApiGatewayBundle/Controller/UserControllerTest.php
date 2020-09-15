<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
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
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loginUserAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::login
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::getSettings
     *
     * @throws Exception
     */
    public function testLoginAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/loginRequest.json', ['id' => '123456789']);
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::logoutAction
     *
     * @throws Exception
     */
    public function testLogoutAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/logoutRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::registerAction
     *
     * @throws Exception
     */
    public function testRegisterAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/registerRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::activateAction
     *
     * @throws Exception
     */
    public function testActivateAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/activateRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::verifyAction
     *
     * @throws Exception
     */
    public function testVerifyAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/verifyRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::setPasswordAction
     *
     * @throws Exception
     */
    public function testSetPasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/setPasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::changePasswordAction
     *
     * @throws Exception
     */
    public function testChangePasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/changePasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::resetPasswordAction
     *
     * @throws Exception
     */
    public function testResetPasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/resetPasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::deleteAction
     *
     * @throws Exception
     */
    public function testDeleteAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/deleteRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::getUserAction
     *
     * @throws Exception
     */
    public function testGetAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/getUserRequest.json');
    }

}
