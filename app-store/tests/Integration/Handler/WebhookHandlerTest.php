<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Handler;

use Exception;
use Hanaboso\HbPFAppStore\Handler\WebhookHandler;
use Hanaboso\HbPFAppStore\Model\ApplicationManager;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use HbPFAppStoreTests\DatabaseTestCaseAbstract;

/**
 * Class WebhookHandlerTest
 *
 * @package HbPFAppStoreTests\Integration\Handler
 */
final class WebhookHandlerTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\WebhookHandler::subscribeWebhooks
     *
     * @throws Exception
     */
    public function testSubscribeWebhooks(): void
    {
        $mock = self::createMock(ApplicationManager::class);
        $mock->expects(self::any())->method('subscribeWebhooks');
        $handler = new WebhookHandler($mock);

        $this->createApplicationInstall('user', 'webhook');
        $handler->subscribeWebhooks('webhook', 'user', ['name' => 'name', 'topology' => 'topo']);

        self::assertFake();
    }

    /**
     * @covers \Hanaboso\HbPFAppStore\Handler\WebhookHandler::unsubscribeWebhooks
     *
     * @throws Exception
     */
    public function testUnsubscribeWebhooks(): void
    {
        $mock = self::createMock(ApplicationManager::class);
        $mock->expects(self::any())->method('unsubscribeWebhooks');
        $handler = new WebhookHandler($mock);

        $this->createApplicationInstall('user', 'webhook');
        $handler->unsubscribeWebhooks('webhook', 'user', ['name' => 'name', 'topology' => 'topo']);

        self::assertFake();
    }

    /**
     * @param string  $user
     * @param string  $key
     * @param mixed[] $settings
     *
     * @return ApplicationInstall
     * @throws Exception
     */
    private function createApplicationInstall(
        string $user = 'user',
        string $key = 'key',
        array $settings = []
    ): ApplicationInstall
    {
        $applicationInstall = (new ApplicationInstall())
            ->setUser($user)
            ->setKey($key)
            ->setSettings($settings);
        $this->persistAndFlush($applicationInstall);

        return $applicationInstall;
    }

}
