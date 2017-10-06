<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl;

use CleverConnectors\AppBundle\Model\Systems\WebhookSubscribes;

/**
 *
 */
trait WebhookSystemTrait
{

    /**
     * @var WebhookSubscribes[]
     */
    protected $subscriptions;

    /**
     * @return WebhookSubscribes[]
     */
    public function getWebhookSubscribes(): array
    {
        return $this->subscriptions;
    }

    /**
     * @param WebhookSubscribes $sub
     */
    public function addWebhookSubscribes(WebhookSubscribes $sub): void
    {
        $this->subscriptions[] = $sub;
    }

}