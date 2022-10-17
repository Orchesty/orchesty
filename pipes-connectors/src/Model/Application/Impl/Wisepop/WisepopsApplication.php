<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop;

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
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class WisepopsApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Wisepop
 */
final class WisepopsApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

    private const API_KEY       = 'api_key';
    private const WISEPOOPS_URL = 'https://app.wisepops.com/api1/hooks';
    private const EMAIL_EVENT   = 'email';

    /**
     * @return string
     */
    public function getApplicationType(): string
    {
        return ApplicationTypeEnum::WEBHOOK;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'wisepops';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
    {
        return 'Wisepops';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Build website popups.';
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
        $request = new RequestDto($this->getUri($url), $method, $dto);
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' =>
                    sprintf(
                        'WISEPOPS-API key="%s"',
                        $applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::API_KEY],
                    ),
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
        $form->addField(new Field(Field::TEXT, self::API_KEY, 'API Key', NULL, TRUE));

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('Collected Emails', 'Webhook', '', ['name' => self::EMAIL_EVENT]),
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
            WisepopsApplication::WISEPOOPS_URL,
            Json::encode(
                [
                    'target_url' => $url,
                    'event'      => $subscription->getParameters()['name'],
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
            sprintf('%s?hook_id=%s', WisepopsApplication::WISEPOOPS_URL, $webhook->getWebhookId()),
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

        return Json::decode($dto->getBody())['id'];
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
     * @return bool
     */
    public function isAuthorized(ApplicationInstall $applicationInstall): bool
    {
        return isset($applicationInstall->getSettings()[ApplicationInterface::AUTHORIZATION_FORM][self::API_KEY]);
    }

}
