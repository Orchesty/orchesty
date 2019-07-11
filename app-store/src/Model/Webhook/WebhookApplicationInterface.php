<?php declare(strict_types=1);

namespace Hanaboso\HbPFApplication\Model\Webhook;

use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\Authorization\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;

/**
 * Interface WebhookApplicationInterface
 *
 * @package Hanaboso\HbPFApplication\Model\Webhook
 */
interface WebhookApplicationInterface extends ApplicationInterface
{

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array;

    /**
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     */
    public function getWebhookSubscribeRequestDto(WebhookSubscription $subscription, string $url): RequestDto;

    /**
     * @param string $id
     *
     * @return RequestDto
     */
    public function getWebhookUnsubscribeRequestDto(string $id): RequestDto;

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
     */
    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string;

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool;

}
