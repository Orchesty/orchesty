<?php declare(strict_types=1);

namespace Tests\Controller\HbPFAuthorizationBundle\Controller;

use Hanaboso\PipesFramework\HbPFAuthorizationBundle\Handler\AuthorizationHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\ControllerTestCaseAbstract;

/**
 * Class AuthorizationControllerTest
 *
 * @coversDefaultClass Hanaboso\PipesFramework\HbPFAuthorizationBundle\Controller\AuthorizationController
 * @package Tests\Controller\HbPFAuthorizationBundle\Controller
 */
final class AuthorizationControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers ::authorization()
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
     * @covers ::authorization()
     */
    public function testAuthorizationFail(): void
    {
        $this->prepareAuthorizationHandlerMock('authorize');

        $this->client->request('POST', '/api/authorizations/abc/authorize', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers ::authorization()
     */
    public function testSaveToken(): void
    {
        $this->prepareAuthorizationHandlerMock('saveToken');

        $this->client->request('POST', '/api/authorizations/magento2_oauth/save_token', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(302, $response->getStatusCode());
    }

    /**
     * @covers ::getAuthorizations()
     */
    public function testGetAuthorizations(): void
    {
        $returnValue = [
            'name'          => 'name',
            'description'   => 'description',
            'type'          => 'oauth',
            'is_authorized' => TRUE,
        ];

        $this->prepareAuthorizationHandlerMock('getAuthInfo', $returnValue);

        $this->client->request('GET', '/api/authorizations', [], [], [], '{"test":1}');

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