<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFMapperBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler\MapperHandler;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\MockObject\MockObject;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MapperControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFMapperBundle\Controller
 */
final class MapperControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::processTestAction

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
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::processTestAction

     * @throws Exception
     */
    public function testProcessTestAction(): void
    {
        $this->prepareMapperHandlerMockException('processTest', new MapperException());

        $this->client->request('POST', '/mapper/null/process/test', [], [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::processAction

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
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::processAction
     *
     * @throws Exception
     */
    public function testProcessErr(): void
    {
        $this->prepareMapperHandlerMockException('process', new MapperException());
        $this->client->request('POST', '/mapper/null/process', [], [], [], '{"test":1}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::listOfMappersAction
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler\MapperHandler::getMappers
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Handler\MapperHandler
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Loader\MapperLoader::getAllMappers
     *
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
                Json::decode((string) $response->getContent()),
                TRUE
            )
        );
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFMapperBundle\Controller\MapperController::listOfMappersAction
     *
     * @throws Exception
     */
    public function testListOfMapperActionErr(): void
    {
        $this->prepareMapperHandlerMockException('getMappers', new InvalidArgumentException());
        $response = $this->sendGet('/mapper/list');

        self::assertEquals(500, $response->status);
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
     * @param string $methodName
     * @param mixed  $returnValue
     *
     * @throws Exception
     */
    private function prepareMapperHandlerMockException(string $methodName, $returnValue): void
    {
        /** @var MapperHandler|MockObject $mapperHandlerMock */
        $mapperHandlerMock = self::createMock(MapperHandler::class);
        $mapperHandlerMock
            ->method($methodName)
            ->willThrowException($returnValue);

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.mapper.handler.mapper', $mapperHandlerMock);
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
