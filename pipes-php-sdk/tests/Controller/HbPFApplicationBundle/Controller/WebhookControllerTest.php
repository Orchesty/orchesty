<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFApplicationBundle\Controller;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler;
use PipesPhpSdkTests\ControllerTestCaseAbstract;

/**
 * Class WebhookControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFApplicationBundle\Controller
 *
 * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController
 * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler
 * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager
 */
final class WebhookControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController::subscribeWebhooksAction
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler::subscribeWebhooks
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::subscribeWebhooks
     *
     * @throws Exception
     */
    public function testSubscribeWebhooksAction(): void
    {
        $this->mockApplicationHandler();
        $this->insertApp();

        $this->client->request('POST', '/webhook/applications/null/users/bar/subscribe');
        $response = $this->client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController::subscribeWebhooksAction
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
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController::subscribeWebhooksAction
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
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController::unsubscribeWebhooksAction
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler::unsubscribeWebhooks
     * @covers \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager::unsubscribeWebhooks
     *
     * @throws Exception
     */
    public function testUnsubscribeWebhooksAction(): void
    {
        $this->mockApplicationHandler();
        $this->insertApp();

        $this->client->request('POST', '/webhook/applications/null/users/bar/unsubscribe');
        $response = $this->client->getResponse();

        self::assertEquals('200', $response->getStatusCode());
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController::unsubscribeWebhooksAction
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
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController::unsubscribeWebhooksAction
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

        $container = $this->client->getContainer();
        $container->set('hbpf.application.handler.application', $handler);
    }

    /**
     * @param string $fn
     * @param mixed  $return
     */
    private function mockWebhookHandlerException(string $fn, mixed $return): void
    {
        $mock = self::createPartialMock(WebhookHandler::class, [$fn]);
        $mock->expects(self::any())->method($fn)->willThrowException($return);
        self::getContainer()->set('hbpf.application.handler.webhook', $mock);
    }

    /**
     * @throws Exception
     */
    private function insertApp(): void
    {
        $dto = new ApplicationInstall();
        $dto->setKey('null')
            ->setUser('bar');

        $this->pfd($dto);
    }

}
