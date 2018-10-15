<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.10.17
 * Time: 18:13
 */

namespace Tests\Integration\AppBundle\Repository;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use Tests\DatabaseTestCaseAbstract;

/**
 * Class WebhookRepositoryTest
 *
 * @package Tests\Integration\AppBundle\Repository
 */
final class WebhookRepositoryTest extends DatabaseTestCaseAbstract
{

    /**
     *
     */
    public function testIsWebhookRegistred(): void
    {
        /** @var WebhookRepository $repo */
        $repo   = $this->dm->getRepository(Webhook::class);
        $result = $repo->isWebhookRegistred('1', 'sys', 'top', 'nod');
        self::assertFalse($result);

        $wh = new Webhook();
        $wh
            ->setUser('1')
            ->setSystemKey('sys')
            ->setTopologyName('top')
            ->setNodeName('nod')
            ->setWebhookId('11');

        $this->persistAndFlush($wh);

        $result = $repo->isWebhookRegistred('1', 'sys', 'top', 'nod');
        self::assertTrue($result);
        $this->dm->clear();

        $wh = new Webhook();
        $wh
            ->setUser('1')
            ->setSystemKey('sys')
            ->setTopologyName('top1')
            ->setNodeName('nod1')
            ->setWebhookId(NULL);

        $this->persistAndFlush($wh);

        $result = $repo->isWebhookRegistred('1', 'sys', 'top1', 'nod1');
        self::assertFalse($result);
        $this->dm->clear();

        $wh = new Webhook();
        $wh
            ->setUser('2')
            ->setSystemKey('sys')
            ->setTopologyName('top')
            ->setNodeName('nod')
            ->setUnsubscribeFailed(TRUE)
            ->setWebhookId('11');

        $this->persistAndFlush($wh);

        $result = $repo->isWebhookRegistred('2', 'sys', 'top', 'nod');
        self::assertFalse($result);
    }

    /**
     *
     */
    public function testGetWebhooksForTopology(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $web = new Webhook();
            $web->setTopologyName('top')
                ->setSystemKey('null')
                ->setUser($i ? 'user2' : 'user1');
            $this->dm->persist($web);
        }
        $this->dm->flush();

        /** @var WebhookRepository $repo */
        $repo = $this->dm->getRepository(Webhook::class);

        $res = $repo->getWebhooks('top');
        self::assertEquals(2, count($res));
        self::assertEquals('user1', $res[0]['user']);
        self::assertEquals('user2', $res[1]['user']);
    }

    /**
     *
     */
    public function testGetWebhooksForUnsubscribe(): void
    {
        $system = new SystemInstall();
        $system->setUser('2')->setSystem('sys');
        /** @var WebhookRepository $repo */
        $repo = $this->dm->getRepository(Webhook::class);

        $wh = new Webhook();
        $wh
            ->setUser('2')
            ->setSystemKey('sys')
            ->setTopologyName('top')
            ->setNodeName('nod')
            ->setUnsubscribeFailed(TRUE)
            ->setWebhookId('11');
        $this->persistAndFlush($wh);
        $this->dm->clear();

        $res = $repo->getWebhooksForUnsubscribe($system);
        self::assertEmpty($res);

        $wh = new Webhook();
        $wh
            ->setUser('2')
            ->setSystemKey('sys')
            ->setTopologyName('top')
            ->setNodeName('nod')
            ->setUnsubscribeFailed(TRUE)
            ->setWebhookId(NULL);
        $this->persistAndFlush($wh);
        $this->dm->clear();

        $res = $repo->getWebhooksForUnsubscribe($system);
        self::assertEmpty($res);

        $wh = new Webhook();
        $wh
            ->setUser('2')
            ->setSystemKey('sys')
            ->setTopologyName('top')
            ->setNodeName('nod')
            ->setUnsubscribeFailed(FALSE)
            ->setWebhookId(NULL);
        $this->persistAndFlush($wh);
        $this->dm->clear();

        $res = $repo->getWebhooksForUnsubscribe($system);
        self::assertEmpty($res);

        $wh = new Webhook();
        $wh
            ->setUser('2')
            ->setSystemKey('sys')
            ->setTopologyName('top')
            ->setNodeName('nod')
            ->setUnsubscribeFailed(FALSE)
            ->setWebhookId('11');
        $this->persistAndFlush($wh);
        $this->dm->clear();

        $res = $repo->getWebhooksForUnsubscribe($system);
        self::assertNotEmpty($res);
        self::assertCount(1, $res);
    }

}