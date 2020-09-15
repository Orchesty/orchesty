<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Controller;

use Exception;
use Hanaboso\HbPFAppStore\Handler\ApplicationHandler;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\Utils\String\Json;
use HbPFAppStoreTests\ControllerTestCaseAbstract;
use HbPFAppStoreTests\Integration\Model\NullApplication;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ApplicationControllerTest
 *
 * @package HbPFAppStoreTests\Controller
 *
 * @covers  \Hanaboso\HbPFAppStore\Controller\ApplicationController
 */
final class ApplicationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::getUsersApplicationAction
     *
     * @throws Exception
     */
    public function testGetUsersApplication(): void
    {
        $this->mockApplicationHandler(
            Json::decode((string) file_get_contents(sprintf('%s/data/data.json', __DIR__)))
        );

        self::$client->request('GET', '/applications/users/bar');
        $response = self::$client->getResponse();

        self::assertEquals(
            'bar',
            Json::decode((string) $response->getContent())[0][ApplicationInstall::USER]
        );
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::getUsersApplicationAction
     *
     * @throws Exception
     */
    public function testGetUsersApplicationErr(): void
    {
        $this->mockApplicationHandlerException('getApplicationsByUser');

        $response = (array) $this->sendGet('/applications/users/bar');
        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::getApplicationDetailAction
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::getApplicationByKeyAndUser
     * @covers \Hanaboso\HbPFAppStore\Model\Webhook\WebhookManager::getWebhooks
     *
     * @throws Exception
     */
    public function testGetApplicationDetail(): void
    {
        $this->insertApp();
        $application = self::createMock(ApplicationAbstract::class);
        $application->method('toArray')->willReturn(['user' => 'bar']);
        $application->method('getApplicationForm')->willReturn([]);
        self::$container->set('hbpf.application.someApp', $application);

        $response = (array) $this->sendGet('/applications/someApp/users/bar');
        self::assertEquals('200', $response['status']);

        $response = (array) $this->sendGet('/applications/application/users/user');
        self::assertEquals('404', $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::getApplicationDetailAction
     *
     * @throws Exception
     */
    public function testApplicationDetailErr(): void
    {
        $this->mockApplicationHandlerException('getApplicationByKeyAndUser');
        $response = (array) $this->sendGet('/applications/someApp/users/bar');

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::installApplicationAction
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::installApplication
     *
     * @throws Exception
     */
    public function testInstallApplication(): void
    {
        $application = new NullApplication();
        self::$container->set('hbpf.application.example', $application);

        $response = (array) $this->sendPost('/applications/example/users/bar/install', []);
        self::assertEquals('200', $response['status']);

        $response = (array) $this->sendPost('/applications/application/users/user/install', []);
        self::assertEquals('404', $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::installApplicationAction
     *
     * @throws Exception
     */
    public function testInstallApplicationErr(): void
    {
        $this->mockApplicationHandlerException('installApplication');

        $response = (array) $this->sendPost('/applications/example/users/bar/install', []);

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::uninstallApplicationAction
     * @covers \Hanaboso\HbPFAppStore\Handler\ApplicationHandler::uninstallApplication
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::uninstallApplication
     * @covers \Hanaboso\HbPFAppStore\Model\ApplicationManager::unsubscribeWebhooks
     *
     * @throws Exception
     */
    public function testUninstallApplication(): void
    {
        $this->insertApp('null');

        self::$client->request('DELETE', '/applications/null/users/bar/uninstall');
        $response = self::$client->getResponse();

        self::assertEquals(
            'bar',
            Json::decode((string) $response->getContent())[ApplicationInstall::USER]
        );
        self::assertEquals('200', $response->getStatusCode());

        self::$client->request('GET', '/applications/someApp/users/bar');
        $response = self::$client->getResponse();

        self::assertEquals('3002', Json::decode((string) $response->getContent())['error_code']);

        $response = (array) $this->sendDelete('/applications/application/users/user/uninstall');
        self::assertEquals('404', $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::uninstallApplicationAction
     *
     * @throws Exception
     */
    public function testUninstallApplicationErr(): void
    {
        $this->mockApplicationHandlerException('uninstallApplication');
        $response = (array) $this->sendDelete('/applications/null/users/bar/uninstall');

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::updateApplicationSettingsAction
     *
     * @throws Exception
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->mockApplicationHandler(['new_settings' => 'test1']);

        self::$client->request('PUT', '/applications/someApp/users/bar/settings', [], [], [], '{"test":1}');
        $response = self::$client->getResponse();
        self::assertEquals('200', $response->getStatusCode());
        self::assertEquals(
            'test1',
            Json::decode((string) $response->getContent())['new_settings']
        );

        self::$client->request('PUT', '/applications/application/users/user/settings');
        $response = self::$client->getResponse();
        self::assertEquals('404', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::updateApplicationSettingsAction
     *
     * @throws Exception
     */
    public function testUpdateApplicationSettingsErr(): void
    {
        $this->mockApplicationHandlerException('updateApplicationSettings');
        $response = (array) $this->sendPut('/applications/someApp/users/bar/settings', [], ['test' => 1]);

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::saveApplicationPasswordAction
     *
     * @throws Exception
     */
    public function testSaveApplicationPassword(): void
    {
        $this->mockApplicationHandler(['new_passwd' => 'secret']);

        self::$client->request('PUT', '/applications/someApp/users/bar/password', ['password' => 'Passw0rd']);
        $response = self::$client->getResponse();
        self::assertEquals('200', $response->getStatusCode());

        self::$client->request('PUT', '/applications/application/users/user/password', ['password' => 'Passw0rd']);
        $response = self::$client->getResponse();
        self::assertEquals('404', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\ApplicationController::saveApplicationPasswordAction
     *
     * @throws Exception
     */
    public function testSaveApplicationPasswordErr(): void
    {
        $this->mockApplicationHandlerException('updateApplicationPassword');
        $response = (array) $this->sendPut('/applications/someApp/users/bar/password', [], ['passwd' => 'test']);

        self::assertEquals(500, $response['status']);
    }

    /**
     * @param string $fn
     */
    private function mockApplicationHandlerException(string $fn): void
    {
        $mock = self::createPartialMock(ApplicationHandler::class, [$fn]);
        $mock->expects(self::any())->method($fn)->willThrowException(new Exception());
        self::$container->set('hbpf._application.handler.application', $mock);
    }

    /**
     * @param mixed[] $returnValue
     *
     * @throws Exception
     */
    private function mockApplicationHandler(array $returnValue = []): void
    {
        $handler = $this->getMockBuilder(ApplicationHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('saveAuthToken')
            ->willReturn($returnValue);
        $handler->method('updateApplicationPassword')
            ->willReturn($returnValue);
        $handler->method('updateApplicationSettings')
            ->willReturn($returnValue);
        $handler->method('getApplicationsByUser')
            ->willReturn($returnValue);
        $handler->method('authorizeApplication')
            ->willReturnCallback(
                static function (): void {
                }
            );

        /** @var ContainerInterface $container */
        $container = self::$client->getContainer();
        $container->set('hbpf._application.handler.application', $handler);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws Exception
     */
    private function insertApp(string $key = 'someApp', string $user = 'bar'): void
    {
        $dto = new ApplicationInstall();
        $dto->setKey($key)
            ->setUser($user);

        $this->persistAndFlush($dto);
    }

}
