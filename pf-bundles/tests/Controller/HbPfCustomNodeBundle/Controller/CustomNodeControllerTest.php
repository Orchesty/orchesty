<?php declare(strict_types=1);

namespace Tests\Controller\HbPfCustomNodeBundle\Controller;

use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Controller\CustomNodeController;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Tests\ControllerTestCaseAbstract;

/**
 * Class CustomNodeControllerTest
 *
 * @package Tests\Controller\HbPfCustomNodeBundle\Controller
 */
class CustomNodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers JoinerController::sendAction()
     */
    public function testSend(): void
    {
        $this->mockHandler('process');

        $this->client->request('POST', '/custom_node/null/process', [], [], [], ['test' => 'test']);

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['test' => 'test'], json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers CustomNodeController::sendTestAction()
     */
    public function testSendActionTest(): void
    {
        $this->mockHandler('processTest');

        $this->client->request('GET', '/custom_node/null/process/test', [], [], [], '');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

    /**
     * @param string $methodName
     */
    private function mockHandler(string $methodName): void
    {
        $joinerHandlerMock = $this->getMockBuilder(CustomNodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $joinerHandlerMock->method($methodName)->willReturn(['test' => 'test']);

        $this->client->getContainer()->set('hbpf.handler.custom_node', $joinerHandlerMock);
    }

}