<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Document;

use Exception;
use Hanaboso\HbPFAppStore\Document\Webhook;
use HbPFAppStoreTests\DatabaseTestCaseAbstract;

/**
 * Class WebhookTest
 *
 * @package HbPFAppStoreTests\Integration\Document
 *
 * @covers  \Hanaboso\HbPFAppStore\Document\Webhook
 */
final class WebhookTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::getName
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::setName
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::setUser
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::getUser
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::getToken
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::setToken
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::getNode
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::setName
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::getTopology
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::setTopology
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::getApplication
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::setApplication
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::getWebhookId
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::setWebhookId
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::isUnsubscribeFailed
     * @covers \Hanaboso\HbPFAppStore\Document\Webhook::setUnsubscribeFailed
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
        $this->persistAndFlush($webhook);

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
