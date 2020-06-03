<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFApplicationBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\ApplicationHandler;
use Hanaboso\Utils\String\Base64;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ApplicationControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFApplicationBundle\Controller
 */
final class ApplicationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::listOfApplicationsAction
     */
    public function testListOfApplications(): void
    {
        $response = $this->sendGet('/applications');

        self::assertNotEmpty($response->content);
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::listOfApplicationsAction
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
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::authorizeApplicationAction
     */
    public function testAuthorizeApplicationAction(): void
    {
        $this->mockHandler('authorizeApplication');
        $response = $this->sendGet('/applications/key/users/user/authorize?redirect_url=/redirect/url');

        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::authorizeApplicationAction
     */
    public function testAuthorizeApplicationActionNotFound(): void
    {
        $this->mockHandler('authorizeApplication', new ApplicationInstallException());
        $response = $this->sendGet('/applications/key/users/user/authorize?redirect_url=http://example.com');

        self::assertEquals(404, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::authorizeApplicationAction
     */
    public function testAuthorizeApplicationActionErr(): void
    {
        $this->mockHandler('authorizeApplication');
        $response = $this->sendGet('/applications/key/users/user/authorize');

        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenAction
     */
    public function testSetAuthorizationTokenAction(): void
    {
        $this->mockHandler(
            'saveAuthToken',
            [ApplicationInterface::REDIRECT_URL => '/applications/key/users/user/authorize']
        );

        $this->sendRequest(
            'GET',
            '/applications/key/users/user/authorize/token',
            [],
            [],
            [],
            static function (Response $response): void {
                self::assertEquals(200, $response->getStatusCode());
            }
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenAction
     */
    public function testSetAuthorizationTokenActionNotFound(): void
    {
        $this->mockHandler('saveAuthToken', new ApplicationInstallException());
        $response = $this->sendGet('/applications/key/users/user/authorize/token');

        self::assertEquals(404, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenAction
     */
    public function testSetAuthorizationTokenActionErr(): void
    {
        $this->mockHandler('saveAuthToken');
        $response = $this->sendGet('/applications/key/users/user/authorize/token');

        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction
     */
    public function testSetAuthorizationTokenQueryAction(): void
    {
        $this->mockHandler('saveAuthToken', [ApplicationInterface::REDIRECT_URL => '/redirect/url']);
        $user = Base64::base64UrlEncode('user:url');

        $this->sendRequest(
            'GET',
            sprintf('/applications/authorize/token?state=%s', $user),
            [],
            [],
            [],
            static function (Response $response): void {
                self::assertEquals(200, $response->getStatusCode());
            }
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction
     */
    public function testSetAuthorizationTokenQueryActionNotFound(): void
    {
        $this->mockHandler('saveAuthToken', new ApplicationInstallException());
        $response = $this->sendGet('/applications/authorize/token?state={"key":"value"}');

        self::assertEquals(404, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\ApplicationController::setAuthorizationTokenQueryAction
     */
    public function testSetAuthorizationTokenQueryActionErr(): void
    {
        $this->mockHandler('saveAuthToken');
        $response = $this->sendGet('/applications/authorize/token');

        self::assertEquals(500, $response->status);
    }

    /**
     * @param string     $method
     * @param mixed|null $return
     */
    private function mockHandler(string $method, $return = NULL): void
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

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.application.handler', $handler);
    }

}
