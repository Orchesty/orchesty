<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 3:18 PM
 */

namespace Tests\Connector;

use CcApi\ApiEntity\Subscriber;
use CcApi\Connector\ConnectorManager;
use CcApi\Connector\Exception\ConnectorException;
use CcApi\Curl\CurlSender;
use CcApi\Curl\Exception\CurlException;
use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class ConnectorManagerTest
 *
 * @package Tests\Connector
 */
class ConnectorManagerTest extends TestCase
{

    /**
     * @param callable $callback
     *
     * @return CurlSender
     */
    private function createSuccessResponse(callable $callback): CurlSender
    {
        /** @var CurlSender|PHPUnit_Framework_MockObject_MockObject $curlSender */
        $curlSender = $this->createMock(CurlSender::class);
        $curlSender->method('send')->willReturnCallback($callback);

        return $curlSender;
    }

    /**
     * @param Exception $e
     *
     * @return CurlSender
     */
    private function createErrorResponse(Exception $e): CurlSender
    {
        /** @var CurlSender|PHPUnit_Framework_MockObject_MockObject $curlSender */
        $curlSender = $this->createMock(CurlSender::class);
        $curlSender->method('send')->willThrowException($e);

        return $curlSender;
    }

    /**
     * @covers ConnectorManager::parseBody()
     */
    public function testParseError(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);

            return new Response();
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $this->expectException(ConnectorException::class);
        $this->expectExceptionMessage('Parser error: Syntax error');
        $this->expectExceptionCode(ConnectorException::PARSER_ERROR);
        $cm->getAllSystems();
    }

    /**
     * @covers ConnectorManager::send()
     */
    public function testRequestError(): void
    {
        $cm = new ConnectorManager($this->createErrorResponse(new CurlException('Request error')));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Request error');
        $this->expectExceptionCode(ConnectorException::REQUEST_ERROR);
        $cm->getAllSystems();
    }

    /**
     * @covers ConnectorManager::getAllSystems()
     */
    public function testGetAllSystem(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::GET, $request->getMethod());
            $this->assertSame('/systems', $request->getUri()->getPath());
            $this->assertSame('group=group1&user=user1', $request->getUri()->getQuery());

            return new Response(200, [], '[{"key":"key","type":"type","name":"name","description":"description"}]');
        };

        $cm      = new ConnectorManager($this->createSuccessResponse($cb));
        $systems = $cm->getAllSystems('group1', 'user1');

        $this->assertSame('key', $systems[0]->getKey());
        $this->assertSame('type', $systems[0]->getType());
        $this->assertSame('name', $systems[0]->getName());
        $this->assertSame('description', $systems[0]->getDescription());
    }

    /**
     * @covers ConnectorManager::getSystem()
     */
    public function testGetSystem(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::GET, $request->getMethod());
            $this->assertSame('/systems/key', $request->getUri()->getPath());

            return new Response(200, [], '{"key":"key","type":"type","name":"name","description":"description"}');
        };

        $cm     = new ConnectorManager($this->createSuccessResponse($cb));
        $system = $cm->getSystem('key');

        $this->assertSame('key', $system->getKey());
        $this->assertSame('type', $system->getType());
        $this->assertSame('name', $system->getName());
        $this->assertSame('description', $system->getDescription());
    }

    /**
     * @covers ConnectorManager::getUserSystem()
     */
    public function testUserGetSystem(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::GET, $request->getMethod());
            $this->assertSame('/user_systems/user/123/system/key', $request->getUri()->getPath());

            $content = '{"key":"key","type":"type","name":"name","description":"description","token":"token","auth_type":"auth_type","synchronized":true,"authorized":false,';
            $content .= '"setting_fields":[{"key":"key","type":"type","value":"value","label":"label","required":true}]}';

            return new Response(200, [], $content);
        };

        $cm         = new ConnectorManager($this->createSuccessResponse($cb));
        $userSystem = $cm->getUserSystem('123', 'key');

        $this->assertSame('key', $userSystem->getKey());
        $this->assertSame('type', $userSystem->getType());
        $this->assertSame('name', $userSystem->getName());
        $this->assertSame('description', $userSystem->getDescription());
        $this->assertSame('token', $userSystem->getToken());
        $this->assertSame('auth_type', $userSystem->getAuthType());
        $this->assertSame(TRUE, $userSystem->isSynchronized());
        $this->assertSame(FALSE, $userSystem->isAuthorized());

        $setting = $userSystem->getSettingFields()[0];
        $this->assertSame('key', $setting->getKey());
        $this->assertSame('type', $setting->getType());
        $this->assertSame('value', $setting->getValue());
        $this->assertSame('label', $setting->getLabel());
        $this->assertSame(TRUE, $setting->isRequired());
    }

    /**
     * @covers ConnectorManager::getAllUserSystems()
     */
    public function testGetAllUserSystem(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::GET, $request->getMethod());
            $this->assertSame('/user_systems/user/123', $request->getUri()->getPath());

            $content = '[{"key":"key","type":"type","name":"name","description":"description","token":"token","auth_type":"auth_type","synchronized":true,"authorized":false}]';

            return new Response(200, [], $content);
        };

        $cm          = new ConnectorManager($this->createSuccessResponse($cb));
        $userSystems = $cm->getAllUserSystems('123');

        $this->assertSame('key', $userSystems[0]->getKey());
        $this->assertSame('type', $userSystems[0]->getType());
        $this->assertSame('name', $userSystems[0]->getName());
        $this->assertSame('description', $userSystems[0]->getDescription());
        $this->assertSame('token', $userSystems[0]->getToken());
        $this->assertSame('auth_type', $userSystems[0]->getAuthType());
        $this->assertSame(TRUE, $userSystems[0]->isSynchronized());
        $this->assertSame(FALSE, $userSystems[0]->isAuthorized());
    }

    /**
     * @covers ConnectorManager::saveUserSystemSetting()
     */
    public function testSaveUserSystemSetting(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::POST, $request->getMethod());
            $this->assertSame('/user_systems/user/123/system/key/settings', $request->getUri()->getPath());
            $this->assertSame('{"lorem":"abc","ipsum":"def"}', $request->getBody()->getContents());

            return new Response(200, [], '');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $cm->saveUserSystemSetting('123', 'key', ['lorem' => 'abc', 'ipsum' => 'def']);
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::installUserSystem()
     */
    public function testInstallUserSystem(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::POST, $request->getMethod());
            $this->assertSame('/user_systems/user/123/system/key/install', $request->getUri()->getPath());
            $this->assertSame('{"token":"abc"}', $request->getBody()->getContents());

            return new Response(200, [], '');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $cm->installUserSystem('123', 'key', 'abc');
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::uninstallUserSystem()
     */
    public function testUninstallUserSystem(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::GET, $request->getMethod());
            $this->assertSame('/user_systems/user/123/system/key/uninstall', $request->getUri()->getPath());
            $this->assertSame('', $request->getBody()->getContents());

            return new Response(200, [], '');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $cm->uninstallUserSystem('123', 'key');
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::synchronizeUserSystem()
     */
    public function testSynchronizeUserSystem(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::GET, $request->getMethod());
            $this->assertSame('/user_systems/user/123/system/key/sync', $request->getUri()->getPath());
            $this->assertSame('', $request->getBody()->getContents());

            return new Response(200, [], '{"running_topologies":2}');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $count = $cm->synchronizeUserSystem('123', 'key');
        $this->assertSame(2, $count);
    }

    /**
     * @covers ConnectorManager::switchUserSystemToken()
     */
    public function testSwitchUserSystemToken(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::PUT, $request->getMethod());
            $this->assertSame('/user_systems/user/123/system/key/switch_token', $request->getUri()->getPath());
            $this->assertSame('{"token":"abc"}', $request->getBody()->getContents());

            return new Response(200, [], '');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $cm->switchUserSystemToken('123', 'key', 'abc');
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::setUserSystemPassword()
     */
    public function testSetUserSystemPassword(): void
    {
        $cb = function (Request $request) {
            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::PUT, $request->getMethod());
            $this->assertSame('/user_systems/user/123/system/key/set_password', $request->getUri()->getPath());
            $this->assertSame('{"password":"abc"}', $request->getBody()->getContents());

            return new Response(200, [], '');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $cm->setUserSystemPassword('123', 'key', 'abc');
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::subscribe()
     */
    public function testSubscribe(): void
    {
        $cb = function (Request $request) {
            $content = '{"email":"email@example.com","first_name":"First Name","last_name":"Last Name","_foreign_id":"123","reactivate":true}';

            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::POST, $request->getMethod());
            $this->assertSame('/event/user/123/create', $request->getUri()->getPath());
            $this->assertSame($content, $request->getBody()->getContents());

            return new Response(200, [], '');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $cm->subscribe('123', (new Subscriber())
            ->setEmail('email@example.com')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setForeignId(123)
            ->setReactivate(TRUE)
        );
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::unSubscribe()
     */
    public function testUnSubscribe(): void
    {
        $cb = function (Request $request) {
            $content = '{"email":"email@example.com","first_name":"First Name","last_name":"Last Name","_foreign_id":"123","reactivate":true}';

            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::POST, $request->getMethod());
            $this->assertSame('/event/user/123/unsubscribe', $request->getUri()->getPath());
            $this->assertSame($content, $request->getBody()->getContents());

            return new Response(200, [], '');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $cm->unSubscribe('123', (new Subscriber())
            ->setEmail('email@example.com')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setForeignId(123)
            ->setReactivate(TRUE)
        );
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::hardBounce()
     */
    public function testHardBounce(): void
    {
        $cb = function (Request $request) {
            $content = '{"email":"email@example.com","first_name":"First Name","last_name":"Last Name","_foreign_id":"123","reactivate":true}';

            $this->assertSame('application/json', $request->getHeader('content-type')[0]);
            $this->assertSame(CurlSender::POST, $request->getMethod());
            $this->assertSame('/event/user/123/hard_bounce', $request->getUri()->getPath());
            $this->assertSame($content, $request->getBody()->getContents());

            return new Response(200, [], '');
        };

        $cm = new ConnectorManager($this->createSuccessResponse($cb));

        $cm->hardBounce('123', (new Subscriber())
            ->setEmail('email@example.com')
            ->setFirstName('First Name')
            ->setLastName('Last Name')
            ->setForeignId(123)
            ->setReactivate(TRUE)
        );
        $this->assertTrue(TRUE);
    }

}