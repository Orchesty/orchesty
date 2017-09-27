<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems;

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
     * @param string $url
     *
     * @return RequestDto
     */
    public function getSubscribeRequest(string $url): RequestDto;

    /**
     * @param string $id
     *
     * @return RequestDto
     */
    public function getUnsubscribeRequest(string $id): RequestDto;

    /**
     * @param ResponseDto $response
     *
     * @return string
     */
    public function getWebhookId(ResponseDto $response): string;

    /**
     * @param WebhookSubscribes $sub
     *
     * @return WebhookSystemInterface
     */
    public function addWebhookSubscribes(WebhookSubscribes $sub): WebhookSystemInterface;

}