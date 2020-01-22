<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFApplicationBundle\Controller;

use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
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
                self::assertEquals(302, $response->getStatusCode());
            }
        );
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
                self::assertEquals(302, $response->getStatusCode());
            }
        );
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
            $handler->expects(self::any())->method($method)->willReturn($return);
        } else {
            $handler->expects(self::any())->method($method);
        }

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.application.handler', $handler);
    }

}
