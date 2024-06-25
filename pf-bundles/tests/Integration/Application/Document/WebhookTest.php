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

        self::assertEquals('name', $webhook->getName());
        self::assertEquals('user', $webhook->getUser());
        self::assertEquals('token', $webhook->getToken());
        self::assertEquals('topo', $webhook->getTopology());
        self::assertEquals('1', $webhook->getWebhookId());
        self::assertEquals('node', $webhook->getNode());
        self::assertEquals('app', $webhook->getApplication());
        self::assertTrue($webhook->isUnsubscribeFailed());
    }

}
