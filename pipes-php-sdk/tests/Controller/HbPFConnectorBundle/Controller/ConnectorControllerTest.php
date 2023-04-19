<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFConnectorBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\Application\Document\Dto\CommonObjectDto;
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
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processActionAction
     *
     * @throws Exception
     */
    public function testProcessActionActionErr(): void
    {
        $this->client->request('POST', '/connector/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
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

        self::getContainer()->set('hbpf.handler.connector', $handler);

        $this->client->request('POST', '/connector/magento/action', [], [], [], '{}');

        $response = $this->client->getResponse();
        self::assertEquals(400, $response->getStatusCode());
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

        self::getContainer()->set('hbpf.handler.connector', $handler);

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
            ['body'    => Json::encode(['test' => 'test']),
             'headers' => ['result-code' => 0, 'result-message' => '', 'result-detail' => ''],
            ],
            Json::decode((string) $response->getContent()),
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

        $assert = new CommonObjectDto('null-connector', 'null-key');
        self::assertEquals([$assert->toArray()], Json::decode((string) $response->getContent()));
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
            ->onlyMethods(['processAction', 'processTest'])
            ->getMock();

        $dto = new ProcessDto();
        $dto
            ->setData(Json::encode(['test' => 'test']))
            ->setHeaders([]);
        $handler->method($method)->willReturn($dto);

        self::getContainer()->set('hbpf.handler.connector', $handler);
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
