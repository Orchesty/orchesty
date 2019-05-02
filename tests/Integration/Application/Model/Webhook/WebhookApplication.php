<?php declare(strict_types=1);

namespace Tests\Integration\Application\Model\Webhook;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Application\Base\BasicApplicationInterface;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookSubscription;

/**
 * Class WebhookApplication
 *
 * @package Tests\Integration\Application\Model\Webhook
 */
final class WebhookApplication implements WebhookApplicationInterface
{

    private const SUBSCRIBE   = 'https://example.com/webhook/subscribe';
    private const UNSUBSCRIBE = 'https://example.com/webhook/unsubscribe';

    /**
     * @var WebhookSubscription[]
     */
    private $subscriptions = [];

    /**
     * WebhookApplication constructor.
     */
    public function __construct()
    {
        $this->subscriptions[] = new WebhookSubscription('node', 'topology');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return BasicApplicationInterface::BASIC;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'webhook';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Webhook';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->getName();
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url,
        ?string $data): RequestDto
    {
        $applicationInstall;
        $method;
        $url;
        $data;

        return new RequestDto(CurlManager::METHOD_POST, new Uri('https://example.com'));
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return array
     */
    public function getSettingsFields(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return [];
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $settings
     *
     * @return ApplicationInstall
     */
    public function setApplicationSettings(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall
    {
        $settings;

        return $applicationInstall;
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return $this->subscriptions;
    }

    /**
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookSubscribeRequestDto(WebhookSubscription $subscription, string $url): RequestDto
    {
        $subscription;

        return (new RequestDto(CurlManager::METHOD_POST, new Uri(self::SUBSCRIBE)))->setBody(json_encode([
            'url' => $url,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param string $id
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(string $id): RequestDto
    {
        return (new RequestDto(CurlManager::METHOD_POST, new Uri(self::UNSUBSCRIBE)))->setBody(json_encode([
            'id' => $id,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
     */
    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
    {
        $install;

        return json_decode($dto->getBody(), TRUE, 512, JSON_THROW_ON_ERROR)['id'];
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return json_decode($dto->getBody(), TRUE, 512, JSON_THROW_ON_ERROR)['success'] ?? FALSE;
    }

}
