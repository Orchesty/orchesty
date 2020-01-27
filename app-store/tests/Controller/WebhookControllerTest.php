<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Controller;

use Exception;
use Hanaboso\HbPFAppStore\Handler\WebhookHandler;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use HbPFAppStoreTests\ControllerTestCaseAbstract;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WebhookControllerTest
 *
 * @package HbPFAppStoreTests\Controller
 */
final class WebhookControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::subscribeWebhooksAction
     * @covers \Hanaboso\HbPFAppStore\Handler\WebhookHandler
     * @covers \Hanaboso\HbPFAppStore\Handler\WebhookHandler::subscribeWebhooks
     * @covers \Hanaboso\HbPFAppStore\Model\Webhook\WebhookManager
     * @covers \Hanaboso\HbPFAppStore\Model\Webhook\WebhookManager::subscribeWebhooks
     *
     * @throws Exception
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
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::subscribeWebhooksAction
     */
    public function testSubscribeWebhooksErr(): void
    {
        $this->mockWebhookHandlerException('subscribeWebhooks');
        $response = (array) $this->sendPost('/webhook/applications/null/users/bar/subscribe', []);

        self::assertEquals(500, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::unsubscribeWebhooksAction
     * @covers \Hanaboso\HbPFAppStore\Handler\WebhookHandler::unsubscribeWebhooks
     * @covers \Hanaboso\HbPFAppStore\Model\Webhook\WebhookManager::unsubscribeWebhooks
     *
     * @throws Exception
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
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::unsubscribeWebhooksAction
     */
    public function testUnsubscribeWebhooksErr(): void
    {
        $this->mockWebhookHandlerException('unsubscribeWebhooks');
        $response = (array) $this->sendPost('/webhook/applications/null/users/bar/unsubscribe', []);

        self::assertEquals(500, $response['status']);
    }

    /**
     * @throws Exception
     */
    private function mockApplicationHandler(): void
    {
        $handler = $this->getMockBuilder(WebhookHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handler->method('subscribeWebhooks')
            ->willReturnCallback(
                static function (): void {
                }
            );
        $handler->method('unsubscribeWebhooks')
            ->willReturnCallback(
                static function (): void {
                }
            );

        /** @var ContainerInterface $container */
        $container = self::$client->getContainer();
        $container->set('hbpf._application.handler.application', $handler);
    }

    /**
     * @param string $key
     * @param string $user
     *
     * @throws Exception
     */
    private function insertApp(string $key = 'null', string $user = 'bar'): void
    {
        $dto = new ApplicationInstall();
        $dto->setKey($key)
            ->setUser($user);

        $this->persistAndFlush($dto);
    }

    /**
     * @param string $fn
     */
    private function mockWebhookHandlerException(string $fn): void
    {
        $mock = self::createPartialMock(WebhookHandler::class, [$fn]);
        $mock->expects(self::any())->method($fn)->willThrowException(new ApplicationInstallException());
        self::$container->set('hbpf._application.handler.webhook', $mock);
    }

}
