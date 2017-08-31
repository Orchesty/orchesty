<?php declare(strict_types=1);

namespace Tests\Controller\HbPFAuthorizationBundle\Controller;

use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Controller\AuthorizationController;
use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler\AuthorizationHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\ControllerTestCaseAbstract;

/**
 * Created by PhpStorm.
 * User: stano
 * Date: 30.8.17
 * Time: 13:35
 */
final class AuthorizationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers AuthorizationController::authorization()
     */
    public function testAuthorization(): void
    {
        $this->prepareAuthorizationHandlerMock('authorize');

        $params = ['redirect_url' => 'asdf'];
        $this->client->request('POST', '/api/authorizations/magento2_oauth/authorize', $params, [], [], '{"test":1}');

        /** @var RedirectResponse $response */
        $response = $this->client->getResponse();

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals($params['redirect_url'], $response->getTargetUrl());
    }

    /**
     * @covers AuthorizationController::authorization()
     */
    public function testAuthorizationFail(): void
    {
        $this->prepareAuthorizationHandlerMock('authorize');

        $this->client->request('POST', '/api/authorizations/abc/authorize', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers AuthorizationController::authorization()
     */
    public function testSaveToken(): void
    {
        $this->prepareAuthorizationHandlerMock('saveToken');

        $this->client->request('POST', '/api/authorizations/magento2_oauth/save_token', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(302, $response->getStatusCode());
    }

    /**
     * @covers AuthorizationController::getAuthorizationsInfo()
     */
    public function testGetAuthorizationsInfo(): void
    {
        $returnValue = [
            'name'          => 'name',
            'description'   => 'description',
            'type'          => 'oauth',
            'is_authorized' => TRUE,
        ];

        $this->prepareAuthorizationHandlerMock('getAuthInfo', $returnValue);

        $this->client->request('GET', '/api/authorization/info', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($returnValue, $content);
    }

    /**
     * @param string $methodName
     * @param string $returnValue
     */
    private function prepareAuthorizationHandlerMock(string $methodName, $returnValue = 'Test'): void
    {
        $authorizationHandlerMock = $this->getMockBuilder(AuthorizationHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $authorizationHandlerMock->method($methodName)->willReturn($returnValue);

        $this->client->getContainer()->set('hbpf.handler.authorization', $authorizationHandlerMock);
    }

}