<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Webhook;

use CleverConnectors\AppBundle\Document\Webhook;
use Tests\DatabaseTestCaseAbstract;
use Tests\PrivateTrait;

/**
 * Class WebhookManagerTest
 *
 * @package Tests\Integration\AppBundle\Model
 */
final class WebhookManagerTest extends DatabaseTestCaseAbstract
{

    use PrivateTrait;

    /**
     *
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->dm->getConnection()->dropDatabase('clever-connectors');
    }

    /**
     *
     */
    public function testSubscribe(): void
    {
        $system  = $this->container->get('systems.null.user.group');
        $webhook = $this->container->get('cc.webhook.manager');

        $this->container->get('cc.systems.manager')->installSystem('someUser', 'null.user.group', 'token');

        // Subscribe
        $webhook->subscribe($system, 'someUser', 'token');
        /** @var Webhook $res */
        $res = $this->dm->getRepository(Webhook::class)->findAll();
        self::assertEquals(1, count($res));
        $res = $res[0];
        self::assertEquals('someUser', $res->getUser());
        self::assertEquals('top', $res->getTopologyName());
        self::assertEquals('node', $res->getNodeName());
        self::assertEquals('null.user.group', $res->getSystemKey());

        // Update
        $webhook->update($system, 'someUser', 'token');
        $res = $this->dm->getRepository(Webhook::class)->findAll();
        self::assertEquals(1, count($res));

        // Unsubscribe
        $webhook->unsubscribe($system, 'someUser');
        $res = $this->dm->getRepository(Webhook::class)->findAll();
        self::assertEquals(0, count($res));
    }

}