<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: venca
 * Date: 10/4/17
 * Time: 3:18 PM
 */

namespace Tests\Connector;

use CcApi\Connector\ConnectorManager;
use CcApi\Connector\Exception\ConnectorException;
use CcApi\Curl\CurlSender;
use CcApi\Curl\Exception\CurlException;
use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class ConnectorManagerTest
 *
 * @package Tests\Connector
 */
class ConnectorManagerTest extends TestCase
{

    /**
     * @param string $content
     *
     * @return CurlSender
     */
    private function createSuccessResponse(string $content = ''): CurlSender
    {
        /** @var StreamInterface|PHPUnit_Framework_MockObject_MockObject $stream */
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($content);

        /** @var ResponseInterface|PHPUnit_Framework_MockObject_MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getBody')->willReturn($stream);

        /** @var CurlSender|PHPUnit_Framework_MockObject_MockObject $curlSender */
        $curlSender = $this->createMock(CurlSender::class);
        $curlSender->method('send')->willReturn($response);

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
        $cm = new ConnectorManager($this->createSuccessResponse());

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
        $content = '[{"key":"key","type":"type","name":"name","description":"description"}]';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $systems = $cm->getAllSystems();

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
        $content = '{"key":"key","type":"type","name":"name","description":"description"}';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

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
        $content = '{"key":"key","type":"type","name":"name","description":"description","token":"token","synchronized":true,"authorized":false,';
        $content .= '"setting_fields":[{"key":"key","type":"type","value":"value","label":"label","required":true}]}';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $userSystem = $cm->getUserSystem('123', 'key');

        $this->assertSame('key', $userSystem->getKey());
        $this->assertSame('type', $userSystem->getType());
        $this->assertSame('name', $userSystem->getName());
        $this->assertSame('description', $userSystem->getDescription());
        $this->assertSame('token', $userSystem->getToken());
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
        $content = '[{"key":"key","type":"type","name":"name","description":"description","token":"token","synchronized":true,"authorized":false}]';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $userSystems = $cm->getAllUserSystems('123');

        $this->assertSame('key', $userSystems[0]->getKey());
        $this->assertSame('type', $userSystems[0]->getType());
        $this->assertSame('name', $userSystems[0]->getName());
        $this->assertSame('description', $userSystems[0]->getDescription());
        $this->assertSame('token', $userSystems[0]->getToken());
        $this->assertSame(TRUE, $userSystems[0]->isSynchronized());
        $this->assertSame(FALSE, $userSystems[0]->isAuthorized());
    }

    /**
     * @covers ConnectorManager::saveUserSystemSetting()
     */
    public function testSaveUserSystemSetting(): void
    {
        $content = '';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $cm->saveUserSystemSetting('123', '', []);
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::installUserSystem()
     */
    public function testInstallUserSystem(): void
    {
        $content = '';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $cm->installUserSystem('123', '', 'abc');
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::uninstallUserSystem()
     */
    public function testUninstallUserSystem(): void
    {
        $content = '';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $cm->uninstallUserSystem('123', '');
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::synchronizeUserSystem()
     */
    public function testSynchronizeUserSystem(): void
    {
        $content = '';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $cm->synchronizeUserSystem('123', '');
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::switchUserSystemToken()
     */
    public function testSwitchUserSystemToken(): void
    {
        $content = '';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $cm->switchUserSystemToken('123', '', '123');
        $this->assertTrue(TRUE);
    }

    /**
     * @covers ConnectorManager::authorizeUserSystem()
     */
    public function testAuthorizeUserSystem(): void
    {
        $content = '';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $cm->authorizeUserSystem('123', '', 'http://example.com');
        $this->assertTrue(TRUE);
    }

}