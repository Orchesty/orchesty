<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFUserBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\PipesFramework\ApiGateway\Exception\LicenseException;
use Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController as ApiUserController;
use Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\PipesFramework\User\Document\UserSettings;
use Hanaboso\PipesFramework\User\Filter\UserDocumentFilter;
use Hanaboso\PipesFramework\User\Manager\UserManager;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFUserBundle\Controller
 */
#[CoversClass(ApiUserController::class)]
#[CoversClass(UserController::class)]
#[CoversClass(UserHandler::class)]
#[CoversClass(UserManager::class)]
#[CoversClass(UserDocumentFilter::class)]
#[AllowMockObjectsWithoutExpectations]
final class UserControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testGetAllUsers(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/getAllUsersRequest.json',
            [
                'created' => '2020-02-26 12:00:00',
                'id'      => '5e565d74eb437f16e475a2e2',
            ],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetAllUsersEntity(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getAllUsers']);
        $handler->expects(self::atLeastOnce())->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);

        self::assertSame(200, $controller->getAllUsersAction(new Request())->getStatusCode());
    }

    /**
     * @throws Exception
     */
    public function testLogoutAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/logoutRequest.json',
            ['token' => 'jwt', 'id' => '619614a1379b5b1d6c512df2'],
        );
    }

    /**
     * @throws Exception
     */
    public function testLoggedUserAction(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/loggedUserRequest.json',
            ['token' => 'jwt', 'id' => '619614a1379b5b1d6c512df2'],
        );
    }

    /**
     * @throws Exception
     */
    public function testRegisterAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/registerRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testActivateAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/activateRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testVerifyAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/verifyRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testSetPasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/setPasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testChangePasswordAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/changePasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testResetPasswordAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/resetPasswordRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testDeleteAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/deleteRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testGetAllUsersErr(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getAllUsers']);
        $handler->expects(self::atLeastOnce())->method('getAllUsers')->willThrowException(new MongoDBException());
        $controller = new UserController($handler,);
        $controller->setLogger(new Logger('logger'));

        self::expectException(MongoDBException::class);
        $controller->getAllUsersAction(new Request());
    }

    /**
     * @throws Exception
     */
    public function testGetAllUsersInvalid(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getAllUsers']);
        $handler->expects(self::atLeastOnce())->method('getAllUsers')->willThrowException(new LicenseException());
        $controller = new UserController($handler,);
        $controller->setLogger(new Logger('logger'));

        self::expectException(LicenseException::class);
        $controller->getAllUsersAction(new Request());
    }

    /**
     * @throws Exception
     */
    public function testGetUser(): void
    {
        $this->assertResponseLogged(
            $this->jwt,
            __DIR__ . '/data/getUserRequest.json',
            ['id' => '5e57a1ace2a2c66a577b8ff2'],
            [':id' => $this->user->getId()],
        );
    }

    /**
     * @throws Exception
     */
    public function testGetUserEntityError(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getUserDetail', 'getAllUsers']);
        $handler->expects(self::atLeastOnce())->method('getUserDetail')->willThrowException(new UserManagerException());
        $handler->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);

        self::expectException(UserManagerException::class);
        $controller->getUserAction('123');
    }

    /**
     * @throws Exception
     */
    public function testSaveSettings(): void
    {
        $this->assertResponse(
            __DIR__ . '/data/saveSettingsRequest.json',
            [],
            [':id' => $this->user->getId()],
            requestHeadersReplacements: [self::$AUTHORIZATION => $this->jwt],
        );
        $this->dm->clear();
        $repository = $this->dm->getRepository(UserSettings::class);
        /** @var UserSettings $setting */
        $setting = $repository->findOneBy(['userId' => $this->user->getId()]);

        self::assertEquals(1, count($repository->findAll()));
        self::assertEquals(['some' => 'settings'], $setting->getSettings());
        self::assertSame($this->user->getId(), $setting->getUserId());
    }

    /**
     * @throws Exception
     */
    public function testSaveSettingsErrNoUser(): void
    {
        $this->assertResponse(__DIR__ . '/data/saveSettingsErrRequest.json');
    }

    /**
     * @throws Exception
     */
    public function testSaveSettingsErr(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['saveSettings', 'getAllUsers']);
        $handler->expects(self::atLeastOnce())->method('saveSettings')->willThrowException(new MongoDBException());
        $handler->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        $user = new User()
            ->setPassword('passw0rd')
            ->setEmail('email@example.com');
        $this->pfd($user);

        self::expectException(MongoDBException::class);
        $controller->saveUserSettingsAction(new Request(), $user->getId());
    }

    /**
     * @throws Exception
     */
    public function testLoginUserActionErr(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['login', 'getAllUsers']);
        $handler->expects(self::atLeastOnce())->method('login')->willThrowException(new SecurityManagerException());
        $handler->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        self::expectException(SecurityManagerException::class);
        $controller->loginUserAction(new Request());
    }

    /**
     * @throws Exception
     */
    public function testLoginUserActionErrPipes(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['login', 'getAllUsers']);
        $handler->expects(self::atLeastOnce())->method('login')->willThrowException(new PipesFrameworkException());
        $handler->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        self::expectException(PipesFrameworkException::class);
        $controller->loginUserAction(new Request());
    }

    /**
     * @throws Exception
     */
    public function testLoginUserAction(): void
    {
        $repository = $this->dm->getRepository(User::class);
        /** @var User $user */
        $user = $repository->findOneBy(['email' => 'test@example.com']);

        $settings = (new UserSettings())->setUserId($user->getId())->setSettings(['data' => 'someData']);
        $this->pfd($settings);

        $this->assertResponse(
            __DIR__ . '/data/loginUserRequest.json',
            ['id' => '5e57a1ace2a2c66a577b8ff2', 'token' => 'jwt'],
        );
    }

}
