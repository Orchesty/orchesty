<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 5.10.17
 * Time: 18:13
 */

namespace Tests\Integration\AppBundle\Repository;

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
    }

}