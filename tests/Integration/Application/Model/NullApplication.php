<?php declare(strict_types=1);

namespace Tests\Integration\Application\Model;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Application\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesFramework\Application\Document\ApplicationInstall;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class NullApplication
 *
 * @package Tests\Integration\Application\Model
 */
class NullApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

    /**
     * NullApplication constructor.
     *
     * @param OAuth2Provider $provider
     */
    public function __construct(OAuth2Provider $provider)
    {
        parent::__construct($provider);
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return AuthorizationTypeEnum::BASIC;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'null';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'null';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'This is null app.';
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
        string $method, ?string $url,
        ?string $data
    ): RequestDto
    {
        $applicationInstall;
        $data;
        $url;

        return new RequestDto($method, new Uri(''));
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return array
     */
    public function getSettingsFields(ApplicationInstall $applicationInstall): array
    {
        return $applicationInstall->getSettings();
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param array              $settings
     *
     * @return ApplicationInstall
     */
    public function setApplicationSettings(ApplicationInstall $applicationInstall, array $settings): ApplicationInstall
    {
        return $applicationInstall->setSettings($settings);
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        $applicationInstall;

        return TRUE;
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return 'auth.url';
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return 'token.url';
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [];
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
        $url;

        return new RequestDto('', new Uri($url));
    }

    /**
     * @param string $id
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(string $id): RequestDto
    {
        $id;

        return new RequestDto('', new Uri(''));
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
        $dto;

        return TRUE;
    }

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK;
    }

}