<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Manager\ApplicationManager;
use Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class WebhookHandlerTest
 *
 * @package PipesPhpSdkTests\Integration\HbPFApplicationBundle\Handler
 */
final class WebhookHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler::subscribeWebhooks
     *
     * @throws Exception
     */
    public function testSubscribeWebhooks(): void
    {
        $mock = self::createMock(ApplicationManager::class);
        $mock->expects(self::any())->method('subscribeWebhooks');
        $handler = new WebhookHandler($mock);

        $this->createApplicationInstall('webhook');
        $handler->subscribeWebhooks('webhook', 'user', ['name' => 'name', 'topology' => 'topo']);

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\PipesPhpSdk\HbPFApplicationBundle\Handler\WebhookHandler::unsubscribeWebhooks
     *
     * @throws Exception
     */
    public function testUnsubscribeWebhooks(): void
    {
        $mock = self::createMock(ApplicationManager::class);
        $mock->expects(self::any())->method('unsubscribeWebhooks');
        $handler = new WebhookHandler($mock);

        $this->createApplicationInstall('webhook');
        $handler->unsubscribeWebhooks('webhook', 'user', ['name' => 'name', 'topology' => 'topo']);

        self::assertFake();
    }

    /**
     * @param string $key
     *
     * @throws Exception
     */
    private function createApplicationInstall(string $key = 'key'): void
    {
        $applicationInstall = (new ApplicationInstall())
            ->setUser('user')
            ->setKey($key)
            ->setSettings([]);
        $this->pfd($applicationInstall);
    }

}
