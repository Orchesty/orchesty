<?php declare(strict_types=1);

namespace Tests\Controller\HbPFMapperBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Utils\Json;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler\MapperHandler;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class MapperControllerTest
 *
 * @package Tests\Controller\HbPFMapperBundle\Controller
 */
final class MapperControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::processTestAction()
     * @throws Exception
     */
    public function testProcessTest(): void
    {
        $this->prepareMapperHandlerMock('processTest', []);

        $this->client->request('POST', '/mapper/null/process/test', [], [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::processAction()
     * @throws Exception
     */
    public function testProcess(): void
    {
        $params = ['abc' => 'def'];
        $this->prepareMapperHandlerMock('process', $params);

        $this->client->request('POST', '/mapper/null/process', $params, [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($params, Json::decode((string) $response->getContent()));
    }

    /**
     * @param string $methodName
     * @param mixed  $returnValue
     *
     * @throws Exception
     */
    private function prepareMapperHandlerMock(string $methodName, $returnValue = 'Test'): void
    {
        /** @var MapperHandler|MockObject $mapperHandlerMock */
        $mapperHandlerMock = self::createMock(MapperHandler::class);
        $mapperHandlerMock
            ->method($methodName)
            ->willReturn($returnValue);

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.mapper.handler.mapper', $mapperHandlerMock);
    }

    /**
     * @throws Exception
     */
    public function testGetListOfCustomNodes(): void
    {
        $this->mockNodeControllerHandler();
        $this->client->request('GET', '/mapper/list');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertTrue(
            in_array(
                'handler.mapper',
                Json::decode((string) $response->getContent())
            )
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws Exception
     */
    private function mockNodeControllerHandler(): void
    {
        $handler = $this->getMockBuilder(MapperHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getMappers');
    }

}
