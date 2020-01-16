<?php declare(strict_types=1);

namespace Tests\Controller\HbPfCustomNodeBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use Hanaboso\Utils\String\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class CustomNodeControllerTest
 *
 * @package Tests\Controller\HbPfCustomNodeBundle\Controller
 */
final class CustomNodeControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::sendAction()
     *
     * @throws Exception
     */
    public function testSend(): void
    {
        $this->mockHandler();

        $this->client->request(
            'POST',
            '/custom_node/null/process',
            [],
            [],
            [],
            Json::encode(['test' => 'test'])
        );

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            ['test' => 'test'],
            Json::decode((string) $response->getContent())
        );
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Controller\CustomNodeController::sendTestAction()
     *
     * @throws Exception
     */
    public function testSendActionTest(): void
    {
        $this->mockHandler();

        $this->client->request('GET', '/custom_node/null/process/test', [], [], [], '');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], Json::decode((string) $response->getContent()));
    }

    /**
     * @throws Exception
     */
    private function mockHandler(): void
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders(['test' => 'test'])
            ->setData(Json::encode(['test' => 'test']));

        /** @var CustomNodeHandler|MockObject $joinerHandlerMock */
        $joinerHandlerMock = self::createMock(CustomNodeHandler::class);
        $joinerHandlerMock
            ->method('process')
            ->willReturn($dto);
        $joinerHandlerMock
            ->method('processTest')
            ->willReturnCallback(
                static function (): void {
                }
            );

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.handler.custom_node', $joinerHandlerMock);
    }

    /**
     * @throws Exception
     */
    public function testGetListOfCustomNodes(): void
    {
        $this->mockNodeControllerHandler();
        $this->client->request('GET', '/custom_node/list');

        /** @var Response $response */
        $response = $this->client->getResponse();

        self::assertTrue(
            in_array(
                'microsleep500000',
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
        $handler = $this->getMockBuilder(CustomNodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getCustomNodes');
    }

}
