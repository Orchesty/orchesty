<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Controller;

use Exception;
use Hanaboso\HbPFAppStore\Handler\WebhookHandler;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use HbPFAppStoreTests\ControllerTestCaseAbstract;

/**
 * Class WebhookControllerTest
 *
 * @package HbPFAppStoreTests\Controller
 *
 * @covers  \Hanaboso\HbPFAppStore\Controller\WebhookController
 * @covers  \Hanaboso\HbPFAppStore\Handler\WebhookHandler
 * @covers  \Hanaboso\HbPFAppStore\Model\Webhook\WebhookManager
 */
final class WebhookControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::subscribeWebhooksAction
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
        $response = self::$client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::subscribeWebhooksAction
     *
     * @throws Exception
     */
    public function testSubscribeWebhooksErr(): void
    {
        $this->mockWebhookHandlerException('subscribeWebhooks', new ApplicationInstallException());
        $response = (array) $this->sendPost('/webhook/applications/null/users/bar/subscribe', []);

        self::assertEquals(404, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::subscribeWebhooksAction
     *
     * @throws Exception
     */
    public function testSubscribeWebhooksErr2(): void
    {
        $this->mockWebhookHandlerException('subscribeWebhooks', new Exception());
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
        $response = self::$client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::unsubscribeWebhooksAction
     *
     * @throws Exception
     */
    public function testUnsubscribeWebhooksErr(): void
    {
        $this->mockWebhookHandlerException('unsubscribeWebhooks', new ApplicationInstallException());
        $response = (array) $this->sendPost('/webhook/applications/null/users/bar/unsubscribe', []);

        self::assertEquals(404, $response['status']);
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Controller\WebhookController::unsubscribeWebhooksAction
     *
     * @throws Exception
     */
    public function testUnsubscribeWebhooksErr2(): void
    {
        $this->mockWebhookHandlerException('unsubscribeWebhooks', new Exception());
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
                },
            );
        $handler->method('unsubscribeWebhooks')
            ->willReturnCallback(
                static function (): void {
                },
            );

        $container = self::$client->getContainer();
        $container->set('hbpf._application.handler.application', $handler);
    }

    /**
     * @throws Exception
     */
    private function insertApp(): void
    {
        $dto = new ApplicationInstall();
        $dto->setKey('null')
            ->setUser('bar');

        $this->persistAndFlush($dto);
    }

    /**
     * @param string $fn
     * @param mixed  $return
     */
    private function mockWebhookHandlerException(string $fn, mixed $return): void
    {
        $mock = self::createPartialMock(WebhookHandler::class, [$fn]);
        $mock->expects(self::any())->method($fn)->willThrowException($return);
        self::getContainer()->set('hbpf._application.handler.webhook', $mock);
    }

}
