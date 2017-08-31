<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 8/29/17
 * Time: 4:35 PM
 */

namespace Tests\Controller\HbPFJoinerBundle\Controller;

use Hanaboso\PipesFramework\HbPFJoinerBundle\Handler\JoinerHandler;
use Tests\ControllerTestCaseAbstract;

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
        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];
        $this->prepareJoinerHandlerMock('processJoiner', $params);

        $this->client->request('POST', '/api/joiner/null/join', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($params, json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers JoinerController::sendTestAction()
     */
    public function testSendTest(): void
    {
        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];
        $this->prepareJoinerHandlerMock('processJoinerTest', $params);

        $this->client->request('POST', '/api/joiner/null/join/test', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

    /**
     * @param string $methodName
     * @param string $returnValue
     */
    private function prepareJoinerHandlerMock(string $methodName, $returnValue = 'Test'): void
    {
        $joinerHandlerMock = $this->getMockBuilder(JoinerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $joinerHandlerMock->method($methodName)->willReturn($returnValue);

        $this->client->getContainer()->set('hbpf.handler.joiner', $joinerHandlerMock);
    }

}