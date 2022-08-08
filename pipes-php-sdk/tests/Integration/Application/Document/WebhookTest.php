<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Document;

use Exception;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use PipesPhpSdkTests\DatabaseTestCaseAbstract;

/**
 * Class WebhookTest
 *
 * @package PipesPhpSdkTests\Integration\Application\Document
 *
 * @covers  \Hanaboso\PipesPhpSdk\Application\Document\Webhook
 */
final class WebhookTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::getName
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::setName
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::setUser
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::getUser
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::getToken
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::setToken
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::getNode
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::setName
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::getTopology
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::setTopology
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::getApplication
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::setApplication
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::getWebhookId
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::setWebhookId
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::isUnsubscribeFailed
     * @covers \Hanaboso\PipesPhpSdk\Application\Document\Webhook::setUnsubscribeFailed
     *
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
