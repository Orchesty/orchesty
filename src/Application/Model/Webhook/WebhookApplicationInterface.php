<?php declare(strict_types=1);

namespace Hanaboso\PipesFramework\Application\Model\Webhook;

use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Application\Base\ApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;

/**
 * Interface WebhookApplicationInterface
 *
 * @package Hanaboso\PipesFramework\Application\Model\Webhook
 */
interface WebhookApplicationInterface extends ApplicationInterface
{

    public const WEBHOOK = 'webhook';

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
