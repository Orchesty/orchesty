<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Controller\HbPFBatchBundle;

use Exception;
use GuzzleHttp\Psr7\Response;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler;
use Hanaboso\Utils\String\Json;
use PipesPhpSdkTests\ControllerTestCaseAbstract;
use PipesPhpSdkTests\MockServer\Mock;
use PipesPhpSdkTests\MockServer\MockServer;

/**
 * Class WebhookControllerTest
 *
 * @package PipesPhpSdkTests\Controller\HbPFBatchBundle
 *
 * @covers  \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController
 * @covers  \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler
 * @covers  \Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookManager
 */
final class WebhookControllerTest extends ControllerTestCaseAbstract
{

    /**
     * @var MockServer $mockServer
     */
    private MockServer $mockServer;

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Controller\WebhookController::subscribeWebhooksAction
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler::subscribeWebhooks
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::fromArray
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall::toArray
     *
     * @throws Exception
     */
    public function testSubscribeWebhooksAction(): void
    {
        $this->privateSetUp();
        $this->mockApplicationHandler();
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"],"users":["bar"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode((new ApplicationInstall(['name' => 'null', 'user' => 'bar']))->toArray()),
                ),
            ),
        );

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
        $this->privateSetUp();
        $this->mockApplicationHandler();
        $this->mockServer->addMock(
            new Mock(
                '/document/ApplicationInstall?filter={"names":["null"],"users":["bar"]}',
                NULL,
                CurlManager::METHOD_GET,
                new Response(
                    200,
                    [],
                    Json::encode((new ApplicationInstall(['name' => 'null', 'user' => 'bar']))->toArray()),
                ),
            ),
        );

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
     * @return void
     */
    private function privateSetUp(): void
    {
        $this->mockServer = new MockServer();
        self::getContainer()->set('hbpf.worker-api', $this->mockServer);
    }

}
