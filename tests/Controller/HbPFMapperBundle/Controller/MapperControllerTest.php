<?php declare(strict_types=1);

namespace Tests\Controller\HbPFMapperBundle\Controller;

use Hanaboso\PipesFramework\HbPFMapperBundle\Controller\MapperController;
use Hanaboso\PipesFramework\HbPFMapperBundle\Handler\MapperHandler;
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
        $this->prepareMapperHandlerMock('processTest', []);

        $this->client->request('POST', '/api/mapper/null/process/test', [], [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers MapperController::processAction()
     */
    public function testProcess(): void
    {
        $params = ['abc' => 'def'];
        $this->prepareMapperHandlerMock('process', $params);

        $this->client->request('POST', '/api/mapper/null/process', $params, [], [], '{"test":1}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals($params, json_decode($response->getContent(), TRUE));
    }

    /**
     * @param string $methodName
     * @param string $returnValue
     */
    private function prepareMapperHandlerMock(string $methodName, $returnValue = 'Test'): void
    {
        $mapperHandlerMock = $this->getMockBuilder(MapperHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mapperHandlerMock->method($methodName)->willReturn($returnValue);

        $this->client->getContainer()->set('hbpf.mapper.handler.mapper', $mapperHandlerMock);
    }

}