<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Application\Document;

use Exception;
use Hanaboso\PipesFramework\Application\Document\Webhook;
use PHPUnit\Framework\Attributes\CoversClass;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class WebhookTest
 *
 * @package PipesFrameworkTests\Integration\Application\Document
 */
#[CoversClass(Webhook::class)]
final class WebhookTest extends DatabaseTestCaseAbstract
{

    /**
     * @throws Exception
     */
    public function testDocument(): void
    {
        $webhook = (new Webhook())
            ->setName('name')
            ->setUser('user')
            ->setToken('token')
            ->setTopology('topo')
            ->setWebhookId('1')
            ->setApplication('app')
            ->setNode('node')
            ->setUnsubscribeFailed(TRUE);
        $this->pfd($webhook);

        self::assertSame('name', $webhook->getName());
        self::assertSame('user', $webhook->getUser());
        self::assertSame('token', $webhook->getToken());
        self::assertSame('topo', $webhook->getTopology());
        self::assertSame('1', $webhook->getWebhookId());
        self::assertSame('node', $webhook->getNode());
        self::assertSame('app', $webhook->getApplication());
        self::assertTrue($webhook->isUnsubscribeFailed());
    }

}
