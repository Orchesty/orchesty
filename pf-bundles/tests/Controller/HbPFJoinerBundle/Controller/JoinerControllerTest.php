<?php declare(strict_types=1);

namespace Tests\Controller\HbPFJoinerBundle\Controller;

use Exception;
use Hanaboso\PipesFramework\HbPFJoinerBundle\Handler\JoinerHandler;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tests\ControllerTestCaseAbstract;

/**
 * Class JoinerControllerTest
 *
 * @package Tests\Controller\HbPFJoinerBundle\Controller
 */
final class JoinerControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers JoinerController::sendAction()
     * @throws Exception
     */
    public function testSend(): void
    {
        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];
        $this->prepareJoinerHandlerMock('processJoiner', $params);

        $this->client->request('POST', '/joiner/null/join', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($params, json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers JoinerController::sendTestAction()
     * @throws Exception
     */
    public function testSendTest(): void
    {
        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];
        $this->prepareJoinerHandlerMock('processJoinerTest', $params);

        $this->client->request('POST', '/joiner/null/join/test', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

    /**
     * @param string $methodName
     * @param mixed  $returnValue
     *
     * @throws Exception
     */
    private function prepareJoinerHandlerMock(string $methodName, $returnValue = 'Test'): void
    {
        /** @var JoinerHandler|MockObject $joinerHandlerMock */
        $joinerHandlerMock = self::createMock(JoinerHandler::class);
        $joinerHandlerMock
            ->method($methodName)
            ->willReturn($returnValue);

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.handler.joiner', $joinerHandlerMock);
    }

    /**
     * @throws Exception
     */
    public function testGetListOfConnectors(): void
    {
        $this->mockConnectorsHandler();
        $this->client->request('GET', '/joiner/list');

        $response = $this->client->getResponse();

        self::assertTrue(in_array('null', json_decode($response->getContent())));
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException
     */
    private function mockConnectorsHandler(): void
    {
        $handler = $this->getMockBuilder(JoinerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getJoiners');
    }

}
