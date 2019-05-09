<?php declare(strict_types=1);

namespace Tests\Controller\HbPfCustomNodeBundle\Controller;

use Exception;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\PipesFramework\HbPFCustomNodeBundle\Handler\CustomNodeHandler;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        $this->mockHandler('process');

        $this->client->request(
            'POST',
            '/custom_node/null/process',
            [],
            [],
            [],
            (string) json_encode(['test' => 'test'])
        );

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(['test' => 'test'], json_decode($response->getContent(), TRUE));
    }

    /**
     * @covers CustomNodeController::sendTestAction()
     * @throws Exception
     */
    public function testSendActionTest(): void
    {
        $this->mockHandler('processTest');

        $this->client->request('GET', '/custom_node/null/process/test', [], [], [], '');

        $response = $this->client->getResponse();

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], json_decode($response->getContent(), TRUE));
    }

    /**
     * @param string $methodName
     *
     * @throws Exception
     */
    private function mockHandler(string $methodName): void
    {
        $dto = new ProcessDto();
        $dto
            ->setHeaders(['test' => 'test'])
            ->setData((string) json_encode(['test' => 'test']));

        /** @var CustomNodeHandler|MockObject $joinerHandlerMock */
        $joinerHandlerMock = self::createMock(CustomNodeHandler::class);
        $joinerHandlerMock
            ->method($methodName)
            ->willReturn($dto);

        /** @var ContainerInterface $container */
        $container = $this->client->getContainer();
        $container->set('hbpf.handler.custom_node', $joinerHandlerMock);
    }

    /**
     * @throws ReflectionException
     */
    public function testGetListOfCustomNodes(): void
    {
        $this->mockNodeControllerHandler();
        $this->client->request('GET', '/custom_node/list');

        $response = $this->client->getResponse();

        self::assertTrue(in_array('microsleep500000', json_decode($response->getContent())));
        self::assertEquals(200, $response->getStatusCode());
    }

    /**
     * @throws ReflectionException
     */
    private function mockNodeControllerHandler(): void
    {
        $handler = $this->getMockBuilder(CustomNodeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('getCustomNodes');
    }

}
