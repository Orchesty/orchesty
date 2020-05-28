<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFJoinerBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Exception\JoinerException;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JoinerControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFJoinerBundle\Controller
 */
final class JoinerControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::sendAction
     * @throws Exception
     */
    public function testSend(): void
    {
        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];
        $this->prepareJoinerHandlerMock($params);

        $this->client->request('POST', '/joiner/null/join', [], [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($params, Json::decode((string) $response->getContent()));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::sendAction
     * @throws Exception
     */
    public function testSendErr(): void
    {
        $this->mockJoinerHandlerException();
        $this->client->request('POST', '/joiner/null/join', [], [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::sendAction
     * @throws Exception
     */
    public function testSendErr2(): void
    {
        $this->mockJoinerHandlerException();
        $this->client->request('POST', '/joiner/null/join', [], [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::sendTestAction
     * @throws Exception
     */
    public function testSendTest(): void
    {
        $params = [
            'data'  => ['abc' => 'def'],
            'count' => 1,
        ];
        $this->prepareJoinerHandlerMock($params);

        $this->client->request('POST', '/joiner/null/join/test', [], [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], Json::decode((string) $response->getContent()));
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::sendTestAction
     *
     * @throws Exception
     */
    public function testSendTestErr(): void
    {
        $this->mockJoinerHandlerException();
        $this->client->request('POST', '/joiner/null/join/test', [], [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::listOfJoinersAction
     *
     * @throws Exception
     */
    public function testGetListOfConnectors(): void
    {
        $this->mockConnectorsHandler();
        $this->client->request('GET', '/joiner/list');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertTrue(
            in_array(
                'null',
                Json::decode((string) $response->getContent()),
                TRUE
            )
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::listOfJoinersAction
     *
     * @throws Exception
     */
    public function testGetListOfConnectorsErr(): void
    {
        $this->mockJoinerHandlerException();
        $this->client->request('GET', '/joiner/list');

        /** @var Response $response */
        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @param mixed $returnValue
     *
     * @throws Exception
     */
    private function prepareJoinerHandlerMock($returnValue = 'Test'): void
    {
        $joinerHandlerMock = self::createMock(JoinerHandler::class);
        $joinerHandlerMock
            ->method('processJoiner')
            ->willReturn($returnValue);
        $joinerHandlerMock
            ->method('processJoinerTest')
            ->willReturnCallback(
                static function (): void {
                }
            );

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.handler.joiner', $joinerHandlerMock);
    }

    /**
     *
     */
    private function mockJoinerHandlerException(): void
    {
        $joinerHandlerMock = self::createPartialMock(
            JoinerHandler::class,
            ['processJoiner', 'processJoinerTest', 'getJoiners']
        );
        $joinerHandlerMock->expects(self::any())->method('processJoiner')->willThrowException(new JoinerException());
        $joinerHandlerMock->expects(self::any())->method('processJoinerTest')
            ->willThrowException(new JoinerException());
        $joinerHandlerMock->expects(self::any())->method('getJoiners')->willThrowException(new Exception());

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.handler.joiner', $joinerHandlerMock);
    }

    /**
     * @throws Exception
     */
    private function mockConnectorsHandler(): void
    {
        $handler = $this->getMockBuilder(JoinerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getJoiners');
    }

}
