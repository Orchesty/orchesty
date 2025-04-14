<?php declare(strict_types=1);

namespace PipesPhpSdkTests\Integration\Application\Manager\Webhook;

use Exception;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\Utils\String\Json;

/**
 * Class WebhookApplication
 *
 * @package PipesPhpSdkTests\Integration\Application\Manager\Webhook
 */
final class WebhookApplication extends ApplicationAbstract implements WebhookApplicationInterface
{

    private const string SUBSCRIBE   = 'https://example.com/webhook/subscribe';
    private const string UNSUBSCRIBE = 'https://example.com/webhook/unsubscribe';

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
        return AuthorizationTypeEnum::BASIC->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'webhook';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
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
     * @param ProcessDtoAbstract $dto
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getRequestDto(
        ProcessDtoAbstract $dto,
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
    ): RequestDto
    {
        $applicationInstall;
        $method;
        $url;
        $data;

        return new RequestDto(new Uri('https://example.com'), CurlManager::METHOD_POST, $dto);
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $formStack = new FormStack();

        return $formStack->addForm(new Form('webhookForm','Webhook form'));
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
        string $url,
    ): RequestDto
    {
        $applicationInstall;
        $subscription;

        return (new RequestDto(new Uri(self::SUBSCRIBE),CurlManager::METHOD_POST, new ProcessDto()))
            ->setBody(Json::encode(['url' => $url,]));
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param Webhook            $webhook
     *
     * @return RequestDto
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        Webhook $webhook,
    ): RequestDto
    {
        $applicationInstall;

        return (new RequestDto(new Uri(self::UNSUBSCRIBE),CurlManager::METHOD_POST, new ProcessDto()))
            ->setBody(Json::encode(['id' => $webhook->getId(),]));
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
        return ApplicationTypeEnum::WEBHOOK->value;
    }

}
