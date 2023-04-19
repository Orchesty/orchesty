<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFUserBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
use Hanaboso\PipesFramework\ApiGateway\Exception\LicenseException;
use Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController;
use Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler;
use Hanaboso\PipesFramework\User\Document\UserSettings;
use Hanaboso\UserBundle\Document\User;
use Hanaboso\UserBundle\Model\Security\SecurityManagerException;
use Hanaboso\UserBundle\Model\User\UserManagerException;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Monolog\Logger;
use PipesFrameworkTests\ControllerTestCaseAbstract;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserControllerTest
 *
 * @package PipesFrameworkTests\Controller\HbPFUserBundle\Controller
 *
 * @covers  \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController
 * @covers  \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController
 * @covers  \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler
 * @covers  \Hanaboso\PipesFramework\User\Manager\UserManager
 */
final class UserControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::getAllUsersAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getAllUsersAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::getAllUsers
     * @covers \Hanaboso\PipesFramework\User\Manager\UserManager::getArrayOfUsers
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::prepareSearchQuery
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::setDocument
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::filterCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::orderCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::searchableCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::useTextSearch
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getAllUsersAction
     *
     * @throws Exception
     */
    public function testGetAllUsersEntity(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getAllUsers']);
        $handler->expects(self::any())->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);

        self::assertEquals(200, $controller->getAllUsersAction(new Request())->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loggedUserAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loggedUserAction
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::registerAction
     *
     * @throws Exception
     */
    public function testRegisterAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/registerRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::activateAction
     *
     * @throws Exception
     */
    public function testActivateAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/activateRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::verifyAction
     *
     * @throws Exception
     */
    public function testVerifyAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/verifyRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::setPasswordAction
     *
     * @throws Exception
     */
    public function testSetPasswordAction(): void
    {
        $this->assertResponse(__DIR__ . '/data/setPasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::changePasswordAction
     *
     * @throws Exception
     */
    public function testChangePasswordAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/changePasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::resetPasswordAction
     *
     * @throws Exception
     */
    public function testResetPasswordAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/resetPasswordRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::deleteAction
     *
     * @throws Exception
     */
    public function testDeleteAction(): void
    {
        $this->assertResponseLogged($this->jwt, __DIR__ . '/data/deleteRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::getAllUsersAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getAllUsersAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::getAllUsers
     * @covers \Hanaboso\PipesFramework\User\Manager\UserManager::getArrayOfUsers
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::prepareSearchQuery
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::setDocument
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::filterCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::orderCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::searchableCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::useTextSearch
     *
     * @throws Exception
     */
    public function testGetAllUsersErr(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getAllUsers']);
        $handler->expects(self::any())->method('getAllUsers')->willThrowException(new MongoDBException());
        $controller = new UserController($handler,);
        $controller->setLogger(new Logger('logger'));

        self::expectException(MongoDBException::class);
        $controller->getAllUsersAction(new Request());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::getAllUsersAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getAllUsersAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::getAllUsers
     * @covers \Hanaboso\PipesFramework\User\Manager\UserManager::getArrayOfUsers
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::prepareSearchQuery
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::setDocument
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::filterCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::orderCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::searchableCols
     * @covers \Hanaboso\PipesFramework\User\Filter\UserDocumentFilter::useTextSearch
     *
     * @throws Exception
     */
    public function testGetAllUsersInvalid(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getAllUsers']);
        $handler->expects(self::any())->method('getAllUsers')->willThrowException(new LicenseException());
        $controller = new UserController($handler,);
        $controller->setLogger(new Logger('logger'));

        self::expectException(LicenseException::class);
        $controller->getAllUsersAction(new Request());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::getUserAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getUserAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::getUserDetail
     * @covers \Hanaboso\PipesFramework\User\Document\UserSettings::getUserId
     * @covers \Hanaboso\PipesFramework\User\Document\UserSettings::getSettings
     *
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
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getUserAction
     *
     * @throws Exception
     */
    public function testGetUserEntityError(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getUserDetail', 'getAllUsers']);
        $handler->expects(self::any())->method('getUserDetail')->willThrowException(new UserManagerException());
        $handler->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);

        self::expectException(UserManagerException::class);
        $controller->getUserAction('123');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::saveUserSettingsAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::saveUserSettingsAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::saveSettings
     * @covers \Hanaboso\PipesFramework\User\Document\UserSettings::setUserId
     * @covers \Hanaboso\PipesFramework\User\Document\UserSettings::getUserId
     * @covers \Hanaboso\PipesFramework\User\Document\UserSettings::getSettings
     * @covers \Hanaboso\PipesFramework\User\Document\UserSettings::setSettings
     *
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
        self::assertEquals($this->user->getId(), $setting->getUserId());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::getUser
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::saveUserSettingsAction
     *
     * @throws Exception
     */
    public function testSaveSettingsErrNoUser(): void
    {
        $this->assertResponse(__DIR__ . '/data/saveSettingsErrRequest.json');
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFApiGatewayBundle\Controller\UserController::saveUserSettingsAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::saveUserSettingsAction
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Handler\UserHandler::saveSettings
     *
     * @throws Exception
     */
    public function testSaveSettingsErr(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['saveSettings', 'getAllUsers']);
        $handler->expects(self::any())->method('saveSettings')->willThrowException(new MongoDBException());
        $handler->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd');
        $this->pfd($user);

        self::expectException(MongoDBException::class);
        $controller->saveUserSettingsAction(new Request(), $user->getId());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loginUserAction
     *
     * @throws Exception
     */
    public function testLoginUserActionErr(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['login', 'getAllUsers']);
        $handler->expects(self::any())->method('login')->willThrowException(new SecurityManagerException());
        $handler->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        self::expectException(SecurityManagerException::class);
        $controller->loginUserAction(new Request());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loginUserAction
     *
     * @throws Exception
     */
    public function testLoginUserActionErrPipes(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['login', 'getAllUsers']);
        $handler->expects(self::any())->method('login')->willThrowException(new PipesFrameworkException());
        $handler->method('getAllUsers')->willReturn(['paging' => ['total' => 1]]);
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        self::expectException(PipesFrameworkException::class);
        $controller->loginUserAction(new Request());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loginUserAction
     *
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
