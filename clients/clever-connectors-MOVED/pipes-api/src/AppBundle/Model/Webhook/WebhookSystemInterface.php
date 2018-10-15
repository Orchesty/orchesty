<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Webhook;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\SystemInterface;

/**
 * Interface WebhookSystemInterface
 *
 * @package CleverConnectors\AppBundle\Model\Webhook
 */
interface WebhookSystemInterface extends SystemInterface
{

    /**
     * @return array
     */
    public function getWebhookSubscribes(): array;

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     */
    public function getSubscribeRequester(SystemInstall $systemInstall): RequesterInterface;

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface;

    /**
     * @param WebhookSubscribes $sub
     */
    public function addWebhookSubscribes(WebhookSubscribes $sub): void;

}