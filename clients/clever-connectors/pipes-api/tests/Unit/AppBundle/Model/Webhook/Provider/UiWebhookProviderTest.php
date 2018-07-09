<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: radek.jirsa
 * Date: 23.10.17
 * Time: 15:33
 */

namespace Tests\Unit\AppBundle\Model\Webhook\Provider;

use CleverConnectors\AppBundle\Document\Webhook;
use CleverConnectors\AppBundle\Model\Webhook\Provider\UiWebhookProvider;
use CleverConnectors\AppBundle\Repository\WebhookRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Tests\Integration\AppBundle\Model\Systems\Impl\NullSystem;
use Tests\KernelTestCaseAbstract;

/**
 * Class UiWebhookProviderTest
 *
 * @package Tests\Unit\AppBundle\Model\Webhook\Provider
 */
final class UiWebhookProviderTest extends KernelTestCaseAbstract
{

    /**
     *
     */
    public function testSubscribe(): void
    {
        $provider = $this->mockProvider();
        $oauth2   = $this->ownContainer->get('hbpf.providers.oauth2_provider');
        $system   = new NullSystem($oauth2);
        $provider->subscribe($system, '123', '456');
    }

    /**
     *
     */
    public function testUnSubscribe(): void
    {
        $provider = $this->mockProvider();
        $oauth2   = $this->ownContainer->get('hbpf.providers.oauth2_provider');
        $system   = new NullSystem($oauth2);
        $provider->unsubscribe($system, '123');
    }

    /**
     *
     */
    public function testUpdate(): void
    {
        $provider = $this->mockProvider();
        $oauth2   = $this->ownContainer->get('hbpf.providers.oauth2_provider');
        $system   = new NullSystem($oauth2);
        $provider->update($system, '123', '456');
    }

    /**
     * ------------------------------------- HELPERS ---------------------------------
     */

    /**
     * @return UiWebhookProvider
     */
    private function mockProvider(): UiWebhookProvider
    {
        $webhook = $this->createMock(Webhook::class);
        $webhook->method('getWebhookId')->willReturn('123456');

        $webhookRepo = $this->createMock(WebhookRepository::class);
        $webhookRepo->method('isWebhookRegistred')->willReturn(FALSE);
        $webhookRepo->method('findBy')->willReturn([$webhook]);

        $dm = $this->createMock(DocumentManager::class);
        $dm->method('persist')->willReturn(NULL);
        $dm->method('flush')->willReturn(NULL);
        $dm->method('remove')->willReturn(NULL);
        $dm->method('getRepository')->willReturn($webhookRepo);

        return new UiWebhookProvider($dm);
    }

}