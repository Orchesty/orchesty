<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 8/29/17
 * Time: 4:35 PM
 */

namespace Tests\Controller\HbPFJoinerBundle\Controller;

use Hanaboso\PipesFramework\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Loader\JoinerLoader;
use Tests\ControllerTestCaseAbstract;
use Tests\Unit\HbPFJoinerBundle\Handler\JoinerHandler;

/**
 * Class JoinerControllerTest
 *
 * @package Controller\HbPFJoinerBundle\Controller
 */
final class JoinerControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers JoinerController::sendAction()
     */
    public function testSend(): void
    {
        $this->prepareJoinerHandlerMock('processJoiner');

        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];
        $this->client->request('POST', '/api/joiner/null/join', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers JoinerController::sendAction()
     */
    public function testSendFail(): void
    {
        $this->prepareJoinerHandlerMock('processJoiner');

        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];

        $this->client->request('POST', '/api/joiner/abc/join', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals('ERROR', $content['status']);
        self::assertEquals(JoinerException::JOINER_SERVICE_NOT_FOUND, $content['error_code']);
    }

    /**
     * @covers JoinerController::sendTestAction()
     */
    public function testSendTest(): void
    {
        $this->prepareJoinerHandlerMock('processJoinerTest');

        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];

        $this->client->request('POST', '/api/joiner/null/join/test', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers JoinerController::sendTestAction()
     */
    public function testSendTestFail(): void
    {
        $this->prepareJoinerHandlerMock('processJoinerTest');

        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];

        $this->client->request('POST', '/api/joiner/abc/join/test', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals('ERROR', $content['status']);
        self::assertEquals(JoinerException::JOINER_SERVICE_NOT_FOUND, $content['error_code']);
    }

    /**
     * @covers JoinerController::sendTestAction()
     */
    public function testSendTestFailData(): void
    {
        $this->prepareJoinerHandlerMock('processJoinerTest');

        $params = [
            'count' => 1,
        ];

        $this->client->request('POST', '/api/joiner/null/join/test', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals('ERROR', $content['status']);
        self::assertEquals(JoinerException::MISSING_DATA_IN_REQUEST, $content['error_code']);
    }

    /**
     * @covers JoinerController::sendTestAction()
     */
    public function testSendTestFailCount(): void
    {
        $this->prepareJoinerHandlerMock('processJoinerTest');

        $params = [
            'data'  => ['abc' => 'def'],
        ];

        $this->client->request('POST', '/api/joiner/null/join/test', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals('ERROR', $content['status']);
        self::assertEquals(JoinerException::MISSING_DATA_IN_REQUEST, $content['error_code']);
    }

    /**
     * @param string $methodName
     */
    private function prepareJoinerHandlerMock(string $methodName): void
    {
        $joinerHandlerMock = $this->getMockBuilder(JoinerHandler::class)
            ->setConstructorArgs([new JoinerLoader($this->container)])
            ->setMethods([$methodName])
            ->getMock();

        $joinerHandlerMock->method($methodName)->willReturn('Test');

        $this->container->set('hbpf.handler.joiner', $joinerHandlerMock);
    }

}