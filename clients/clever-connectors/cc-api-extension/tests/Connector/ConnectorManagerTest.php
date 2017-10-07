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
    public function testGetSystem(): void
    {
        $content = '[{"key":"key","type":"type","name":"name","description":"description"}]';
        $cm      = new ConnectorManager($this->createSuccessResponse($content));

        $systems = $cm->getAllSystems();

        $this->assertSame('key', $systems[0]->getKey());
        $this->assertSame('type', $systems[0]->getType());
        $this->assertSame('name', $systems[0]->getName());
        $this->assertSame('description', $systems[0]->getDescription());
    }

}