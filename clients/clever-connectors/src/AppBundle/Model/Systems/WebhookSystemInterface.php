<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface WebhookSystemInterface
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
interface WebhookSystemInterface
{

    /**
     * @return array
     */
    public function getWebhookSubscribes(): array;

    /**
     * @param string $url
     *
     * @return Request
     */
    public function getSubscribeRequest(string $url): Request;

    /**
     * @param string $id
     *
     * @return Request
     */
    public function getUnsubscribeRequest(string $id): Request;

    /**
     * @param Response $response
     *
     * @return string
     */
    public function getWebhookId(Response $response): string;

    /**
     * @return self
     */
    public function addWebhookSubscribes(): self;

}