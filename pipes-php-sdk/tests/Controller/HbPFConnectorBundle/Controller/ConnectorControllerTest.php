<?php declare(strict_types=1);

namespace Tests\Controller\HbPFConnectorBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Handler\ConnectorHandler;
use Hanaboso\Utils\String\Json;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class ConnectorControllerTest
 *
 * @package Tests\Controller\HbPFConnectorBundle\Controller
 */
final class ConnectorControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processEventAction()
     *
     * @throws Exception
     */
    public function testProcessEvent(): void
    {
        $this->mockHandler('processEvent');

        $this->client->request('POST', '/connector/magento/webhook', [], [], [], '{}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            ['test' => 'test'],
            Json::decode((string) $response->getContent())
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFConnectorBundle\Controller\ConnectorController::processActionAction()
     *
     * @throws Exception
     */
    public function testProcessAction(): void
    {
        $this->mockHandler('processAction');

        $this->client->request('POST', '/connector/magento/action', [], [], [], '{}');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            ['test' => 'test'],
            Json::decode((string) $response->getContent())
        );
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
            ->setData(Json::encode(['test' => 'test']))
            ->setHeaders([]);
        $handler->method($method)->willReturn($dto);

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.handler.connector', $handler);
    }

    /**
     * @throws Exception
     */
    public function testGetListOfConnectors(): void
    {
        $this->mockConnectorsHandler();
        $this->client->request('GET', '/connector/list');

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
        $handler = $this->getMockBuilder(ConnectorHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getConnectors');
    }

}
