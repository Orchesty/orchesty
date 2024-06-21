<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesPhpSdkTests\KernelTestCaseAbstract;

/**
 * Class WebhookHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler
 */
#[CoversClass(WebhookHandler::class)]
final class WebhookHandlerTest extends KernelTestCaseAbstract
{

    /**
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
