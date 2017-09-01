<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConnectorBundle\Controller;

use Hanaboso\PipesFramework\HbPFConnectorBundle\Controller\ConnectorController;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Handler\ConnectorHandler;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ConnectorControllerTest
 *
 * @package Tests\Controller\HbPFConnectorBundle\Controller
 */
class ConnectorControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers ConnectorController::processEvent()
     */
    public function testProcessEvent(): void
    {
        $this->mockHandler('processEvent');

        $this->client->request('POST', '/api/connector/magento/topology/asd', [], [], [], '{}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['test' => 'test'], json_decode($response->getContent(), TRUE));
    }

    /**
     * @param string $method
     */
    private function mockHandler(string $method): void
    {
        $handler = $this->getMockBuilder(ConnectorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method($method)->willReturn(['test' => 'test']);

        $this->client->getContainer()->set('hbpf.handler.connector', $handler);
    }

}