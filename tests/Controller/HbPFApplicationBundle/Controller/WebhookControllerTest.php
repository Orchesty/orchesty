<?php declare(strict_types=1);

namespace Tests\Controller\HbPFApplicationBundle\Controller;

use Hanaboso\CommonsBundle\Exception\DateTimeException;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\HbPFApplicationBundle\Handler\WebhookHandler;
use ReflectionException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\ControllerTestCaseAbstract;

/**
 * Class WebhookControllerTest
 *
 * @package Tests\Controller\HbPFApplicationBundle\Controller
 */
final class WebhookControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testSubscribeWebhooksAction(): void
    {
        $this->mockApplicationHandler();
        $this->insertApp();

        self::$client->request('POST', '/webhook/applications/null/users/bar/subscribe');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws DateTimeException
     * @throws ReflectionException
     */
    public function testUnsubscribeWebhooksAction(): void
    {
        $this->mockApplicationHandler();
        $this->insertApp();

        self::$client->request('POST', '/webhook/applications/null/users/bar/unsubscribe');
        /** @var Response $response */
        $response = self::$client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @throws ReflectionException
     */
    private function mockApplicationHandler(): void
    {
        $handler = $this->getMockBuilder(WebhookHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('subscribeWebhooks')
            ->willReturnCallback(function (): void {
            });
        $handler->method('unsubscribeWebhooks')
            ->willReturnCallback(function (): void {
            });

        /** @var ContainerInterface $container */
        $container = self::$client->getContainer();
        $container->set('hbpf._application.handler.application', $handler);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws DateTimeException
     */
    private function insertApp(string $key = 'null', string $user = 'bar'): void
    {
        $dto = new ApplicationInstall();
        $dto->setKey($key)
            ->setUser($user);

        $this->persistAndFlush($dto);
    }

}
