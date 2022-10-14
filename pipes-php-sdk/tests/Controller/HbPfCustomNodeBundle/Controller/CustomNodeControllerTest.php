<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPfCustomNodeBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\Dto\CommonObjectDto;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Exception\CustomNodeException;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Hanaboso\Utils\Exception\PipesFrameworkException;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\ControllerTestCaseAbstract;

/**
 * Class CustomNodeControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPfCustomNodeBundle\Controller
 */
final class CustomNodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::sendAction
     *
     * @throws Exception
     */
    public function testSend(): void
    {
        $this->mockHandler();

        $this->client->request(
            'POST',
            '/custom-node/null/process',
            [],
            [],
            [],
            Json::encode(['test' => 'test']),
        );

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            ['body'    => Json::encode(['test' => 'test']),
             'headers' => ['result-code' => 0, 'result-message' => '', 'result-detail' => '',  'test' => 'test'],
            ],
            Json::decode((string) $response->getContent()),
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::sendAction
     *
     * @throws Exception
     */
    public function testSendErr(): void
    {
        $this->mockNodeControllerException();

        $this->client->request(
            'POST',
            '/custom-node/null/process',
            [],
            [],
            [],
            Json::encode(['test' => 'test']),
        );
        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::sendAction
     *
     * @throws Exception
     */
    public function testSendErr2(): void
    {
        $handler = $this->getMockBuilder(CustomNodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('processAction')->willThrowException(new PipesFrameworkException());
        self::getContainer()->set('hbpf.handler.custom_node', $handler);

        $this->client->request(
            'POST',
            '/custom-node/null/process',
            [],
            [],
            [],
            Json::encode(['test' => 'test']),
        );
        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::sendTestAction
     *
     * @throws Exception
     */
    public function testSendActionTest(): void
    {
        $this->mockHandler();
        $response = $this->sendGet('/custom-node/null/process/test');

        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::sendTestAction
     *
     * @throws Exception
     */
    public function testSendActionTestErr(): void
    {
        $this->mockNodeControllerException();

        $this->client->request('GET', '/custom-node/null/process/test', [], [], [], '');

        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::listOfCustomNodesAction
     *
     * @throws Exception
     */
    public function testGetListOfCustomNodes(): void
    {
        $this->mockNodeControllerHandler();
        $this->client->request('GET', '/custom-node/list');

        $response = $this->client->getResponse();

        $assert = new CommonObjectDto('null', NULL);
        self::assertEquals([$assert->toArray()], Json::decode((string) $response->getContent()));
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::listOfCustomNodesAction
     */
    public function testGetListOfCustomNodesAction(): void
    {
        $this->mockNodeControllerException();
        $this->client->request('GET', '/custom-node/list');

        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    private function mockNodeControllerHandler(): void
    {
        $handler = $this->getMockBuilder(CustomNodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getCustomNodes');
    }

    /**
     * @throws Exception
     */
    private function mockHandler(): void
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders(['test' => 'test'])
            ->setData(Json::encode(['test' => 'test']));

        $joinerHandlerMock = self::createMock(CustomNodeHandler::class);
        $joinerHandlerMock
            ->method('processAction')
            ->willReturn($dto);
        $joinerHandlerMock
            ->method('processTest')
            ->willReturnCallback(
                static function (): void {
                },
            );

        self::getContainer()->set('hbpf.handler.custom_node', $joinerHandlerMock);
    }

    /**
     *
     */
    private function mockNodeControllerException(): void
    {
        $handler = $this->getMockBuilder(CustomNodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();
        $handler->method('getCustomNodes')->willThrowException(new Exception());
        $handler->method('processAction')->willThrowException(new Exception());
        $handler->method('processTest')->willThrowException(new CustomNodeException());
        self::getContainer()->set('hbpf.handler.custom_node', $handler);
    }

}
