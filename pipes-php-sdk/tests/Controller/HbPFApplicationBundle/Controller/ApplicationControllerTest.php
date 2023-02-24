<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFApplicationBundle\Controller;

use Exception;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler;
use Hanaboso\Utils\File\File;
use Hanaboso\Utils\String\Base64;
use Hanaboso\Utils\String\Json;
use LogicException;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
use PipesPhpSdkTests\Integration\Application\Manager\NullApplication;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApplicationControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFApplicationBundle\Controller
 */
final class ApplicationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::listOfApplicationsAction
     *
     * @throws Exception
     */
    public function testListOfApplications(): void
    {
        $response = $this->sendGet('/applications');

        self::assertNotEmpty($response->content);
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::listOfApplicationsAction
     *
     * @throws Exception
     */
    public function testListOfApplicationsErr(): void
    {
        $this->mockHandler('getApplications', new Exception());

        $response = (array) $this->sendGet('/applications');
        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::getApplicationAction
     *
     * @throws Exception
     */
    public function testGetApplication(): void
    {
        $response = $this->sendGet(sprintf('/applications/%s', 'null'));

        self::assertEquals('null-key', $response->content->key);
        self::assertEquals(200, $response->status);

        $response = $this->sendGet(sprintf('/applications/%s', 'example'));
        self::assertEquals(404, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::getApplicationAction
     *
     * @throws Exception
     */
    public function testGetApplicationException(): void
    {
        $this->mockHandler('getApplicationByKey', new Exception());
        $response = $this->sendGet('/applications/application');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::getSynchronousActionsAction
     *
     * @throws Exception
     */
    public function testGetSynchronousActionsAction(): void
    {
        $response = $this->sendGet(sprintf('/applications/%s/sync/list', 'null'));
        self::assertEquals(['testSynchronous', 'returnBody'], (array) $response->content);

        $response = $this->sendGet(sprintf('/applications/%s/sync/list', 'example'));
        self::assertEquals(404, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::getSynchronousActionsAction
     *
     * @throws Exception
     */
    public function testGetSynchronousActionsActionErr(): void
    {
        $this->mockHandler('getSynchronousActions', new Exception());
        $response = $this->sendGet(sprintf('/applications/%s/sync/list', 'null'));
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::runSynchronousActionsAction
     *
     * @throws Exception
     */
    public function testRunSynchronousActionsAction(): void
    {
        $response = $this->sendGet(sprintf('/applications/%s/sync/%s', 'null', 'testSynchronous'));
        self::assertEquals(['ok'], (array) $response->content);

        $response = $this->sendPost(sprintf('/applications/%s/sync/%s', 'null', 'returnBody'), ['data']);
        self::assertEquals(['data'], (array) $response->content);

        $response = $this->sendGet(sprintf('/applications/%s/sync/%s', 'example', 'testSynchronous'));
        self::assertEquals(404, $response->status);

        $response = $this->sendGet(sprintf('/applications/%s/sync/%s', 'null', 'notExist'));
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::runSynchronousActionsAction
     *
     * @throws Exception
     */
    public function testRunSynchronousActionsActionErr(): void
    {
        $this->mockHandler('runSynchronousAction', new Exception());
        $response = $this->sendGet(sprintf('/applications/%s/sync/%s', 'null', 'testSynchronous'));
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::authorizeApplicationAction
     *
     * @throws Exception
     */
    public function testAuthorizeApplicationAction(): void
    {
        $this->mockHandler('authorizeApplication');
        $response = $this->sendGet('/applications/key/users/user/authorize?redirect_url=/redirect/url');

        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::authorizeApplicationAction
     *
     * @throws Exception
     */
    public function testAuthorizeApplicationActionNotFound(): void
    {
        $this->mockHandler('authorizeApplication', new ApplicationInstallException());
        $response = $this->sendGet('/applications/key/users/user/authorize?redirect_url=https://example.com');

        self::assertEquals(404, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::authorizeApplicationAction
     *
     * @throws Exception
     */
    public function testAuthorizeApplicationActionErr(): void
    {
        $this->mockHandler('authorizeApplication');
        $response = $this->sendGet('/applications/key/users/user/authorize');

        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenAction
     *
     * @throws Exception
     */
    public function testSetAuthorizationTokenAction(): void
    {
        $this->mockHandler('saveAuthToken', '/applications/key/users/user/authorize');
        $this->sendRequest(
            'GET',
            '/applications/key/users/user/authorize/token',
            [],
            [],
            [],
            static function (Response $response): void {
                self::assertEquals(200, $response->getStatusCode());
            },
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenAction
     *
     * @throws Exception
     */
    public function testSetAuthorizationTokenActionNotFound(): void
    {
        $this->mockHandler('saveAuthToken', new ApplicationInstallException());
        $response = $this->sendGet('/applications/key/users/user/authorize/token');

        self::assertEquals(404, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenAction
     *
     * @throws Exception
     */
    public function testSetAuthorizationTokenActionErr(): void
    {
        $this->mockHandler('saveAuthToken', new LogicException());
        $response = $this->sendGet('/applications/key/users/user/authorize/token');

        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction
     *
     * @throws Exception
     */
    public function testSetAuthorizationTokenQueryAction(): void
    {
        $this->mockHandler('saveAuthToken', '/redirect/url');
        $user = Base64::base64UrlEncode('user:url');

        $this->sendRequest(
            'GET',
            sprintf('/applications/authorize/token?state=%s', $user),
            [],
            [],
            [],
            static function (Response $response): void {
                self::assertEquals(200, $response->getStatusCode());
            },
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction
     *
     * @throws Exception
     */
    public function testSetAuthorizationTokenQueryActionNotFound(): void
    {
        $this->mockHandler('saveAuthToken', new ApplicationInstallException());
        $response = $this->sendGet('/applications/authorize/token?state={"key":"value"}');

        self::assertEquals(404, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction
     *
     * @throws Exception
     */
    public function testSetAuthorizationTokenQueryActionErr(): void
    {
        $this->mockHandler('saveAuthToken');
        $response = $this->sendGet('/applications/authorize/token');

        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::getUsersApplicationAction
     *
     * @throws Exception
     */
    public function testGetUsersApplication(): void
    {
        $this->mockHandler(
            'getApplicationsByUser',
            Json::decode(File::getContent(sprintf('%s/data/data.json', __DIR__))),
        );

        $this->client->request('GET', '/applications/users/bar');
        $response = $this->client->getResponse();

        self::assertEquals(
            'bar',
            Json::decode((string) $response->getContent())[0][ApplicationInstall::USER],
        );
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::getUsersApplicationAction
     *
     * @throws Exception
     */
    public function testGetUsersApplicationErr(): void
    {
        $this->mockHandler('getApplicationsByUser', new Exception());

        $response = (array) $this->sendGet('/applications/users/bar');
        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::getApplicationDetailAction
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::getApplicationByKeyAndUser
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::getWebhooks
     *
     * @throws Exception
     */
    public function testGetApplicationDetail(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["someApp"],"users":["bar"]}',
                NULL,
                CurlManager::METHOD_GET,
                new GuzzleResponse(200, [], '[{"name":"someApp","user":"bar"}]'),
            ),
        );

        $application = self::createMock(ApplicationAbstract::class);
        $application->method('toArray')->willReturn(['user' => 'bar']);
        $application->method('getApplicationForms')->willReturn([]);
        self::getContainer()->set('hbpf.application.someApp', $application);

        $response = (array) $this->sendGet('/applications/someApp/users/bar');
        self::assertEquals('200', $response['status']);

        $response = (array) $this->sendGet('/applications/application/users/user');
        self::assertEquals('404', $response['status']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::getApplicationDetailAction
     *
     * @throws Exception
     */
    public function testApplicationDetailErr(): void
    {
        $this->mockHandler('getApplicationByKeyAndUser', new Exception());
        $response = (array) $this->sendGet('/applications/someApp/users/bar');

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::installApplicationAction
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::installApplication
     *
     * @throws Exception
     */
    public function testInstallApplication(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["example"],"users":["bar"]}',
                NULL,
                CurlManager::METHOD_GET,
                new GuzzleResponse(200, [], '[]'),
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall',
                Json::decode(
                    '[{"id":null,"user":"bar","name":"example","nonEncryptedSettings":[],"encryptedSettings":"","settings":[],"created":"2023-02-13 14:46:39","updated":"2023-02-13 14:46:39","expires":null,"enabled":false}]',
                ),
                CurlManager::METHOD_POST,
                new GuzzleResponse(200, [], '[{}]'),
                ['created' => '2023-02-13 14:46:39', 'updated' => '2023-02-13 14:46:39'],
            ),
        );
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["example"],"users":["bar"]}',
                NULL,
                CurlManager::METHOD_GET,
                new GuzzleResponse(200, [], '[{}]'),
            ),
        );
        $application = new NullApplication();
        self::getContainer()->set('hbpf.application.example', $application);

        $response = (array) $this->sendPost('/applications/example/users/bar/install', []);
        self::assertEquals('200', $response['status']);

        $response = (array) $this->sendPost('/applications/application/users/user/install', []);
        self::assertEquals('404', $response['status']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::installApplicationAction
     *
     * @throws Exception
     */
    public function testInstallApplicationErr(): void
    {
        $this->mockHandler('installApplication', new Exception());

        $response = (array) $this->sendPost('/applications/example/users/bar/install', []);

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::uninstallApplicationAction
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler::uninstallApplication
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::uninstallApplication
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager::unsubscribeWebhooks
     *
     * @throws Exception
     */
    public function testUninstallApplication(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"],"users":["bar"]}',
                NULL,
                CurlManager::METHOD_GET,
                new GuzzleResponse(200, [], '[{"name":"null","user":"bar"}]'),
            ),
        );
        $this->client->request('DELETE', '/applications/null/users/bar/uninstall');
        $response = $this->client->getResponse();

        self::assertEquals(
            'bar',
            Json::decode((string) $response->getContent())[ApplicationInstall::USER],
        );
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::uninstallApplicationAction
     *
     * @throws Exception
     */
    public function testUninstallApplicationErr(): void
    {
        $this->mockHandler('uninstallApplication', new Exception());
        $response = (array) $this->sendDelete('/applications/null/users/bar/uninstall');

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::updateApplicationSettingsAction
     *
     * @throws Exception
     */
    public function testUpdateApplicationSettings(): void
    {
        $this->mockHandler('updateApplicationSettings', ['new_settings' => 'test1']);

        $this->client->request('PUT', '/applications/someApp/users/bar/settings', [], [], [], '{"test":1}');
        $response = $this->client->getResponse();
        self::assertEquals('200', $response->getStatusCode());
        self::assertEquals(
            'test1',
            Json::decode((string) $response->getContent())['new_settings'],
        );

        $this->client->request('PUT', '/applications/application/users/user/settings');
        $response = $this->client->getResponse();
        self::assertEquals('404', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::updateApplicationSettingsAction
     *
     * @throws Exception
     */
    public function testUpdateApplicationSettingsErr(): void
    {
        $this->mockHandler('updateApplicationSettings', new Exception());
        $response = (array) $this->sendPut('/applications/someApp/users/bar/settings', [], ['test' => 1]);

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::saveApplicationPasswordAction
     *
     * @throws Exception
     */
    public function testSaveApplicationPassword(): void
    {
        $this->mockHandler('updateApplicationPassword', ['new_passwd' => 'secret']);

        $this->client->request(
            'PUT',
            '/applications/someApp/users/bar/password',
            [
                'formKey'  => ApplicationInterface::AUTHORIZATION_FORM,
                'fieldKey' => BasicApplicationInterface::PASSWORD,
                'password' => 'Passw0rd',
            ],
        );
        $response = $this->client->getResponse();
        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::saveApplicationPasswordAction
     *
     * @throws Exception
     */
    public function testSaveApplicationPassword404(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["application"],"users":["user"]}',
                NULL,
                CurlManager::METHOD_GET,
                new GuzzleResponse(200, [], '[]'),
            ),
        );

        $this->client->request(
            'PUT',
            '/applications/application/users/user/password',
            [
                'formKey'  => ApplicationInterface::AUTHORIZATION_FORM,
                'fieldKey' => BasicApplicationInterface::PASSWORD,
                'password' => 'Passw0rd',
            ],
        );
        $response = $this->client->getResponse();
        self::assertEquals('404', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::saveApplicationPasswordAction
     *
     * @throws Exception
     */
    public function testSaveApplicationPasswordErr(): void
    {
        $this->mockHandler('updateApplicationPassword', new Exception());
        $response = (array) $this->sendPut('/applications/someApp/users/bar/password', [], ['passwd' => 'test']);

        self::assertEquals(500, $response['status']);
    }

    /**
     * @param string     $method
     * @param mixed|null $return
     */
    private function mockHandler(string $method, mixed $return = NULL): void
    {
        $handler = self::createPartialMock(ApplicationHandler::class, [$method]);
        if ($return) {
            if ($return instanceof Exception) {
                $handler->expects(self::any())->method($method)->willThrowException($return);
            } else {
                $handler->expects(self::any())->method($method)->willReturn($return);
            }
        } else {
            $handler->expects(self::any())->method($method);
        }

        $container = $this->client->getContainer();
        $container->set('hbpf.application.handler', $handler);
    }

}
