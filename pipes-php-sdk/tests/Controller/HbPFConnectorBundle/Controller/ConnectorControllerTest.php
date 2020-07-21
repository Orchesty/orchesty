<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFConnectorBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Connector\Exception\ConnectorException;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\ControllerTestCaseAbstract;

/**
 * Class ConnectorControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFConnectorBundle\Controller
 */
final class ConnectorControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processEventAction
     *
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $this->mockHandler('processEvent');

        $response = $this->sendPost('/connector/magento/webhook', []);
        self::assertEquals(200, $response->status);
        self::assertEquals('test', $response->content->test);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processEventAction
     *
     * @throws Exception
     */
    public function testProcessEventActionErr(): void
    {
        $this->client->request('POST', '/connector/magento/webhook', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processEventAction
     *
     * @throws Exception
     */
    public function testProcessEventActionErr2(): void
    {
        $handler = self::createPartialMock(ConnectorHandler::class, ['getConnectors']);
        $handler->expects(self::any())->method('getConnectors')->willThrowException(new ConnectorException());

        self::$container->set('hbpf.handler.connector', $handler);

        $this->client->request('POST', '/connector/magento/webhook', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processEventTestAction
     *
     * @throws Exception
     */
    public function testProcessEventTestAction(): void
    {
        $this->mockHandler('processEvent');

        $response = $this->sendGet('/connector/magento/webhook/test');
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processEventTestAction
     *
     * @throws Exception
     */
    public function testProcessEventTestActionErr(): void
    {
        $response = $this->sendGet('/connector/magento/webhook/test');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processActionAction
     *
     * @throws Exception
     */
    public function testProcessActionActionErr(): void
    {
        $this->client->request('POST', '/connector/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processActionAction
     *
     * @throws Exception
     */
    public function testProcessActionActionErr2(): void
    {
        $handler = self::createPartialMock(ConnectorHandler::class, ['getConnectors']);
        $handler->expects(self::any())->method('getConnectors')->willThrowException(new Exception());

        self::$container->set('hbpf.handler.connector', $handler);

        $this->client->request('POST', '/connector/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(500, $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processActionTestAction
     *
     * @throws Exception
     */
    public function testProcessActionTestAction(): void
    {
        $this->mockHandler('processEvent');

        $response = $this->sendGet('/connector/magento/action/test');
        self::assertEquals(200, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processActionTestAction
     *
     * @throws Exception
     */
    public function testProcessActionTestActionErr(): void
    {
        $response = $this->sendGet('/connector/magento/action/test');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::listOfConnectorsAction
     *
     * @throws Exception
     */
    public function testListOfConnectorsAction(): void
    {
        $handler = self::createPartialMock(ConnectorHandler::class, ['getConnectors']);
        $handler->expects(self::any())->method('getConnectors')->willThrowException(new Exception());

        self::$container->set('hbpf.handler.connector', $handler);

        $response = $this->sendGet('/connector/list');
        self::assertEquals(500, $response->status);
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processActionAction
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockHandler('processAction');

        $this->client->request('POST', '/connector/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            ['test' => 'test'],
            Json::decode((string) $response->getContent())
        );
    }

    /**
     * @throws Exception
     */
    public function testGetListOfConnectors(): void
    {
        $this->mockConnectorsHandler();
        $this->client->request('GET', '/connector/list');

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
     * @param string $method
     *
     * @throws Exception
     */
    private function mockHandler(string $method): void
    {
        $handler = $this->getMockBuilder(ConnectorHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['processAction', 'processEvent', 'processTest'])
            ->getMock();

        $dto = new ProcessDto();
        $dto
            ->setData(Json::encode(['test' => 'test']))
            ->setHeaders([]);
        $handler->method($method)->willReturn($dto);

        self::$container->set('hbpf.handler.connector', $handler);
    }

    /**
     * @throws Exception
     */
    private function mockConnectorsHandler(): void
    {
        $handler = $this->getMockBuilder(ConnectorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getConnectors');
    }

}
