<?php declare(strict_types=1);

namespace HbPFAppStoreTests\Integration\Model\Webhook;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\Utils\String\Json;

/**
 * Class WebhookApplication
 *
 * @package HbPFAppStoreTests\Integration\Model\Webhook
 */
final class WebhookApplication extends ApplicationAbstract implements WebhookApplicationInterface
{

    private const SUBSCRIBE   = 'https://example.com/webhook/subscribe';
    private const UNSUBSCRIBE = 'https://example.com/webhook/unsubscribe';

    /**
     * @var WebhookSubscription[]
     */
    private array $subscriptions = [];

    /**
     * WebhookApplication constructor.
     */
    public function __construct()
    {
        $this->subscriptions[] = new WebhookSubscription('name', 'node', 'topology');
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
        ?string $url = NULL,
        ?string $data = NULL
    ): RequestDto
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
     * @return Form
     */
    public function getForm(ApplicationInstall $applicationInstall): Form
    {
        $applicationInstall;

        return new Form();
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $settings
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
     * @param ApplicationInstall  $applicationInstall
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        WebhookSubscription $subscription,
        string $url
    ): RequestDto
    {
        $applicationInstall;
        $subscription;

        return (new RequestDto(CurlManager::METHOD_POST, new Uri(self::SUBSCRIBE)))
            ->setBody(Json::encode(['url' => $url,]));
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $id
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(ApplicationInstall $applicationInstall, string $id): RequestDto
    {
        $applicationInstall;

        return (new RequestDto(CurlManager::METHOD_POST, new Uri(self::UNSUBSCRIBE)))
            ->setBody(Json::encode(['id' => $id,]));
    }

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
     * @throws Exception
     */
    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
    {
        $install;

        return Json::decode($dto->getBody())['id'];
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     *             @throws Exception
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return Json::decode($dto->getBody())['success'] ?? FALSE;
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
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK;
    }

    /**
     * @return Form
     */
    public function getSettingsForm(): Form
    {
        return new Form();
    }

}
