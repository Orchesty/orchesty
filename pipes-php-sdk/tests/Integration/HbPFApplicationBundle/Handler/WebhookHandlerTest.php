<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class WebhookHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler
 */
final class WebhookHandlerTest extends KernelTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler::subscribeWebhooks
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testSubscribeWebhooks(): void
    {
        $mock = self::createMock(ApplicationManager::class);
        $mock->expects(self::any())->method('subscribeWebhooks');
        $handler = new WebhookHandler($mock);

        $handler->subscribeWebhooks('webhook', 'user', ['name' => 'name', 'topology' => 'topo']);

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler::unsubscribeWebhooks
     *
     * @throws Exception
     * @throws GuzzleException
     */
    public function testUnsubscribeWebhooks(): void
    {
        $mock = self::createMock(ApplicationManager::class);
        $mock->expects(self::any())->method('unsubscribeWebhooks');
        $handler = new WebhookHandler($mock);

        $handler->unsubscribeWebhooks('webhook', 'user', ['name' => 'name', 'topology' => 'topo']);

        self::assertFake();
    }

}
