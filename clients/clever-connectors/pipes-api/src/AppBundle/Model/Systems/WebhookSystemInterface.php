<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

use CleverConnectors\AppBundle\Document\SystemInstall;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Interface WebhookSystemInterface
 *
 * @package CleverConnectors\AppBundle\Model\Systems
 */
interface WebhookSystemInterface extends SystemInterface
{

    /**
     * @return array
     */
    public function getWebhookSubscribes(): array;

    /**
     * @param WebhookSubscribes $subscription
     * @param SystemInstall     $systemInstall
     * @param string            $url
     *
     * @return RequestDto
     */
    public function getSubscribeRequest(WebhookSubscribes $subscription, SystemInstall $systemInstall,
                                        string $url): RequestDto;

    /**
     * @param SystemInstall $systemInstall
     * @param string        $webhookId
     *
     * @return RequestDto
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto;

    /**
     * @param ResponseDto $response
     *
     * @return string
     */
    public function getWebhookId(ResponseDto $response): string;

    /**
     * @param WebhookSubscribes $sub
     */
    public function addWebhookSubscribes(WebhookSubscribes $sub): void;

}