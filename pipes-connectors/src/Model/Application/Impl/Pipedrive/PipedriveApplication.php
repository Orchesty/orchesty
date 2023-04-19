<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Pipedrive;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Process\ProcessDto;
use Hanaboso\CommonsBundle\Process\ProcessDtoAbstract;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Document\Webhook;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Manager\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationInterface;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class PipedriveApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Pipedrive
 */
final class PipedriveApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

    public const PIPEDRIVE_URL = 'https://api.pipedrive.com';
    public const ADDED         = 'added';
    public const ACTIVITY      = 'activity';

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'pipedrive';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Pipedrive';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Pipedrive v1';
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
        $join    = strpos($url ?? '', '?') ? '&' : '?';
        $url     = $this->getUri(sprintf('%s%sapi_token=%s', $url, $join, $this->getToken($applicationInstall)));
        $request = new RequestDto($url, $method, $dto);
        $request->setHeaders(
            [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
        );
        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form->addField(new Field(Field::TEXT, BasicApplicationAbstract::USER, 'API token', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return
            isset(
                $applicationInstall->getSettings(
                )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationInterface::USER],
            );
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription(
                'New activity',
                'Webhook',
                '',
                [
                    'action' => self::ADDED,
                    'object' => self::ACTIVITY,
                ],
            ),
        ];
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
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_POST,
            sprintf(
                '%s/v1/webhooks',
                self::PIPEDRIVE_URL,
            ),
            Json::encode(
                [
                    'event_action'     => $subscription->getParameters()['action'],
                    'event_object'     => $subscription->getParameters()['object'],
                    'subscription_url' => $url,
                ],
            ),
        );
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
        return $this->getRequestDto(
            new ProcessDto(),
            $applicationInstall,
            CurlManager::METHOD_DELETE,
            sprintf(
                '%s/v1/webhooks/%s',
                self::PIPEDRIVE_URL,
                $webhook->getWebhookId(),
            ),
        );
    }

    /**
     * @param ResponseDto        $dto
     * @param ApplicationInstall $install
     *
     * @return string
     * @throws JsonException
     */
    public function processWebhookSubscribeResponse(ResponseDto $dto, ApplicationInstall $install): string
    {
        $install;

        return (string) Json::decode($dto->getBody())['data']['id'];
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        return $dto->getStatusCode() === 200;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string
     */
    private function getToken(ApplicationInstall $applicationInstall): string
    {
        return $applicationInstall->getSettings(
        )[ApplicationInterface::AUTHORIZATION_FORM][BasicApplicationAbstract::USER];
    }

}
