<?php declare(strict_types=1);

namespace PipesFrameworkTests\Integration\Application\Document;

use Exception;
use Hanaboso\PipesFramework\Application\Document\Webhook;
use PipesFrameworkTests\DatabaseTestCaseAbstract;

/**
 * Class WebhookTest
 *
 * @package PipesFrameworkTests\Integration\Application\Document
 *
 * @covers  \Hanaboso\PipesFramework\Application\Document\Webhook
 */
final class WebhookTest extends DatabaseTestCaseAbstract
{

    /**
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::getName
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::setName
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::setUser
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::getUser
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::getToken
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::setToken
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::getNode
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::setName
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::getTopology
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::setTopology
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::getApplication
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::setApplication
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::getWebhookId
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::setWebhookId
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::isUnsubscribeFailed
     * @covers \Hanaboso\PipesFramework\Application\Document\Webhook::setUnsubscribeFailed
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
