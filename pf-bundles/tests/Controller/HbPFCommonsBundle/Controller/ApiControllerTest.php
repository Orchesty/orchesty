<?php declare(strict_types=1);

namespace Tests\Controller\HbPFCommonsBundle\Controller;

use Hanaboso\PipesFramework\HbPFCommonsBundle\Controller\ApiController;
use Hanaboso\PipesFramework\HbPFCommonsBundle\Handler\NodeHandler;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ApiControllerTest
 *
 * @package Tests\Controller\HbPFCommonsBundle\Controller
 */
class ApiControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers ApiController::nodeAction
     */
    public function testNode(): void
    {
        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];
        $this->prepareNodeHandlerMock('processData', $params);

        $this->client->request('POST', '/api/nodes/1/process', [], [], ['CONTENT_TYPE' => 'application/json'],
            '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($params, json_decode($response->getContent(), TRUE));
    }

    /**
     * @param string $methodName
     * @param string $returnValue
     */
    private function prepareNodeHandlerMock(string $methodName, $returnValue = 'Test'): void
    {
        $nodeHandlerMock = $this->getMockBuilder(NodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nodeHandlerMock->method($methodName)->willReturn($returnValue);

        $this->client->getContainer()->set('hbpf.commons.node_handler', $nodeHandlerMock);
    }

}