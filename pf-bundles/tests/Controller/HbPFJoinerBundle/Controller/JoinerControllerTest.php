<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: stanislav.kundrat
 * Date: 8/29/17
 * Time: 4:35 PM
 */

namespace Controller\HbPFJoinerBundle\Controller;

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
        $this->prepareJoinerHandlerMock('send');

        $params = ['abc' => 'def'];
        $this->client->request('POST', '/api/joiner/null/join', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($params, json_decode($response->getContent(), TRUE));
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

        $this->container->set('hbpf.joiner.handler', $joinerHandlerMock);
    }

}