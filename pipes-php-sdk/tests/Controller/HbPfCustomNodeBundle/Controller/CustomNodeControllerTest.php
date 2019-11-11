<?php declare(strict_types=1);

namespace Tests\Controller\HbPfCustomNodeBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesPhpSdk\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
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
     * @covers JoinerController::sendAction()
     * @throws Exception
     */
    public function testSend(): void
    {
        $this->mockHandler();

        self::$client->request(
            'POST',
            '/custom_node/null/process',
            [],
            [],
            [],
            (string) json_encode(['test' => 'test'], JSON_THROW_ON_ERROR)
        );

        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(
            ['test' => 'test'],
            json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @covers CustomNodeController::sendTestAction()
     * @throws Exception
     */
    public function testSendActionTest(): void
    {
        $this->mockHandler();

        self::$client->request('GET', '/custom_node/null/process/test', [], [], [], '');

        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode((string) $response->getContent(), TRUE, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws Exception
     */
    private function mockHandler(): void
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders(['test' => 'test'])
            ->setData((string) json_encode(['test' => 'test'], JSON_THROW_ON_ERROR));

        /** @var CustomNodeHandler|MockObject $joinerHandlerMock */
        $joinerHandlerMock = self::createMock(CustomNodeHandler::class);
        $joinerHandlerMock
            ->method('process')
            ->willReturn($dto);
        $joinerHandlerMock
            ->method('processTest')
            ->willReturnCallback(
                function (): void {
                }
            );

        /** @var ContainerInterface $container */
        $container = self::$client->getContainer();
        $container->set('hbpf.handler.custom_node', $joinerHandlerMock);
    }

    /**
     * @throws Exception
     */
    public function testGetListOfCustomNodes(): void
    {
        $this->mockNodeControllerHandler();
        self::$client->request('GET', '/custom_node/list');

        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertTrue(
            in_array(
                'microsleep500000',
                json_decode((string) $response->getContent(), FALSE, 512, JSON_THROW_ON_ERROR)
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
