<?php declare(strict_types=1);

namespace Tests\Controller\HbPFMapperBundle\Controller;

use Hanaboso\PipesFramework\HbPFMapperBundle\Controller\MapperController;
use Hanaboso\PipesFramework\HbPFMapperBundle\Exception\MapperException;
use Hanaboso\PipesFramework\HbPFMapperBundle\Handler\MapperHandler;
use Hanaboso\PipesFramework\HbPFMapperBundle\Loader\MapperLoader;
use Tests\ControllerTestCaseAbstract;

/**
 * Class MapperControllerTest
 *
 * @package Tests\Controller\HbPFMapperBundle\Controller
 */
final class MapperControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers MapperController::processTestAction()
     */
    public function testProcessTest(): void
    {
        $this->prepareMapperHandlerMock('processTest');

        $this->client->request('POST', '/api/mapper/null/process/test', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers MapperController::processTestAction()
     */
    public function testProcessTestFail(): void
    {
        $this->prepareMapperHandlerMock('processTest');

        $this->client->request('POST', '/api/mapper/abc/process/test', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals('ERROR', $content['status']);
        self::assertEquals(MapperException::MAPPER_NOT_EXIST, $content['error_code']);
    }

    /**
     * @covers MapperController::processAction()
     */
    public function testProcess(): void
    {
        $this->prepareMapperHandlerMock('process');

        $params = ['abc' => 'def'];
        $this->client->request('POST', '/api/mapper/null/process', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($params, json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers MapperController::processAction()
     */
    public function testProcessFail(): void
    {
        $this->prepareMapperHandlerMock('process');

        $params = ['abc' => 'def'];
        $this->client->request('POST', '/api/mapper/abc/process', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), TRUE);

        self::assertEquals(500, $response->getStatusCode());
        self::assertEquals('ERROR', $content['status']);
        self::assertEquals(MapperException::MAPPER_NOT_EXIST, $content['error_code']);
    }

    /**
     * @param string $methodName
     */
    private function prepareMapperHandlerMock(string $methodName): void
    {
        $mapperHandlerMock = $this->getMockBuilder(MapperHandler::class)
            ->setConstructorArgs([new MapperLoader($this->container)])
            ->setMethods([$methodName])
            ->getMock();

        $mapperHandlerMock->method($methodName)->willReturn('Test');

        $this->container->set('hbpf.mapper.handler.mapper', $mapperHandlerMock);
    }

}