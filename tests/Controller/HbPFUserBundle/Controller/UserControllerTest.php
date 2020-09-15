<?php declare(strict_types=1);

namespace PipesFrameworkTests\Controller\HbPFUserBundle\Controller;

use Doctrine\ODM\MongoDB\MongoDBException;
use Exception;
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
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd')
            ->setDeleted(TRUE);
        $this->pfd($user);

        $this->assertResponse(
            __DIR__ . '/data/getAllUsersRequest.json',
            [
                'id'      => '5e565d74eb437f16e475a2e2',
                'created' => '2020-02-26 12:00:00',
            ]
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
        $handler->expects(self::any())->method('getAllUsers')->willReturn([]);
        $controller = new UserController($handler);

        self::assertEquals(200, $controller->getAllUsersAction(new Request())->getStatusCode());
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

        self::assertEquals(500, $controller->getAllUsersAction(new Request())->getStatusCode());
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
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd');
        $this->pfd($user);

        $this->assertResponse(
            __DIR__ . '/data/getUserRequest.json',
            ['id' => '5e57a1ace2a2c66a577b8ff2'],
            [':id' => $user->getId()]
        );
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::getUserAction
     *
     * @throws Exception
     */
    public function testGetUserEntityError(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['getUserDetail']);
        $handler->expects(self::any())->method('getUserDetail')->willThrowException(new UserManagerException());
        $controller = new UserController($handler);

        self::assertEquals(500, $controller->getUserAction('123')->getStatusCode());
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
        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd');
        $this->pfd($user);

        $this->assertResponse(__DIR__ . '/data/saveSettingsRequest.json', [], [':id' => $user->getId()]);
        $repository = $this->dm->getRepository(UserSettings::class);
        /** @var UserSettings $setting */
        $setting = $repository->findOneBy(['userId' => $user->getId()]);

        self::assertEquals(1, count($repository->findAll()));
        self::assertEquals(['some' => 'settings'], $setting->getSettings());
        self::assertEquals($user->getId(), $setting->getUserId());
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
        $handler = self::createPartialMock(UserHandler::class, ['saveSettings']);
        $handler->expects(self::any())->method('saveSettings')->willThrowException(new MongoDBException());
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        $user = (new User())
            ->setEmail('email@example.com')
            ->setPassword('passw0rd');
        $this->pfd($user);

        self::assertEquals(500, $controller->saveUserSettingsAction(new Request(), $user->getId())->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loginUserAction
     *
     * @throws Exception
     */
    public function testLoginUserActionErr(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['login']);
        $handler->expects(self::any())->method('login')->willThrowException(new SecurityManagerException());
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        self::assertEquals(400, $controller->loginUserAction(new Request())->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesFramework\HbPFUserBundle\Controller\UserController::loginUserAction
     *
     * @throws Exception
     */
    public function testLoginUserActionErrPipes(): void
    {
        $handler = self::createPartialMock(UserHandler::class, ['login']);
        $handler->expects(self::any())->method('login')->willThrowException(new PipesFrameworkException());
        $controller = new UserController($handler);
        $controller->setLogger(new Logger('logger'));

        self::assertEquals(500, $controller->loginUserAction(new Request())->getStatusCode());
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

        $this->assertResponse(__DIR__ . '/data/loginUserRequest.json', ['id' => '5e57a1ace2a2c66a577b8ff2']);
    }

}
