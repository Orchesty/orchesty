<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConnectorBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\HbPFConnectorBundle\Handler\ConnectorHandler;
use ReflectionException;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ConnectorControllerTest
 *
 * @package Tests\Controller\HbPFConnectorBundle\Controller
 */
final class ConnectorControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers ConnectorController::processEvent()
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $this->mockHandler('processEvent');

        $this->client->request('POST', '/connector/magento/webhook', [], [], [], '{}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['test' => 'test'], json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers ConnectorController::processAction()
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockHandler('processAction');

        $this->client->request('POST', '/connector/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['test' => 'test'], json_decode($response->getContent(), TRUE));
    }

    /**
     * @param string $method
     *
     * @throws Exception
     */
    private function mockHandler(string $method): void
    {
        $handler = $this->getMockBuilder(ConnectorHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['processAction', 'processEvent'])
            ->getMock();

        $dto = new ProcessDto();
        $dto
            ->setData(json_encode(['test' => 'test']))
            ->setHeaders([]);
        $handler->method($method)->willReturn($dto);

        $this->client->getContainer()->set('hbpf.handler.connector', $handler);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetListOfConnectors(): void
    {
        $this->mockConnectorsHandler();
        $this->client->request('GET', '/connector/list');

        $response = $this->client->getResponse();

        self::assertTrue(in_array('null', json_decode($response->getContent())));
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException
     */
    private function mockConnectorsHandler(): void
    {
        $handler = $this->getMockBuilder(ConnectorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getConnectors');
    }

}
