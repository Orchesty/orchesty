<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController;
use Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController as UserControllerBase;
use Hanaboso\UserBundle\Handler\UserHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;

/**
 * Class UserControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFApiGatewayBundle\Controller
 */
#[CoversClass(UserController::class)]
#[CoversClass(UserControllerBase::class)]
#[CoversClass(UserHandler::class)]
final class UserControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testLoginAction(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/UserController/loginRequest.json',
            ['id' => '123456789', 'token' => 'jwt'],
        );
    }

    /**
     * @throws Exception
     */
    public function testLogoutAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UserController/logoutRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/UserController/loggedRequest.json',
            ['id' => '123', 'token' => 'jwt'],
        );
    }

    /**
     * @throws Exception
     */
    public function testRegisterAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/registerRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testActivateAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/activateRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testVerifyAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/verifyRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testSetPasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/setPasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testChangePasswordAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UserController/changePasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testResetPasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/UserController/resetPasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testDeleteAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UserController/deleteRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testGetAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/UserController/getUserRequest.json');
    }

}
