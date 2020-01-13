<?php declare(strict_types=1);

namespace Tests\Controller\HbPFJoinerBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Handler\JoinerHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class JoinerControllerTest
 *
 * @package Tests\Controller\HbPFJoinerBundle\Controller
 */
final class JoinerControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::sendAction()
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
     * @covers \Hanaboso\PipesPhpSdk\HbPFJoinerBundle\Controller\JoinerController::sendTestAction()
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
     * @param mixed $returnValue
     *
     * @throws Exception
     */
    private function prepareJoinerHandlerMock($returnValue = 'Test'): void
    {
        /** @var JoinerHandler|MockObject $joinerHandlerMock */
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
                Json::decode((string) $response->getContent())
            )
        );
        self::assertEquals(200, $response->getStatusCode());
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
