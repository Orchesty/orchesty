<?php declare(strict_types=1);

namespace Hanaboso\PipesPhpSdk\Application\Manager\Webhook;

use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;

/**
 * Interface WebhookApplicationInterface
 *
 * @package Hanaboso\PipesPhpSdk\Application\Manager\Webhook
 */
interface WebhookApplicationInterface extends ApplicationInterface
{

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array;

    /**
     * @param ApplicationInstall  $applicationInstall
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     */
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        WebhookSubscription $subscription,
        string $url,
    ): RequestDto;

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $id
     *
     * @return RequestDto
     */
    public function getWebhookUnsubscribeRequestDto(ApplicationInstall $applicationInstall, string $id): RequestDto;

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
