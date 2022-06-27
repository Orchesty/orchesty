<?php declare(strict_types=1);

namespace PipesFrameworkTests;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationInterface;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Application\Model\Form\FormStack;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\Utils\String\Json;
use JsonException;

/**
 * Class NullApplication
 *
 * @package PipesFrameworkTests
 */
final class NullApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
{

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
    public function getName(): string
    {
        return 'null';
    }

    /**
     * @return string
     */
    public function getPublicName(): string
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
        string $method,
        ?string $url = NULL,
        ?string $data = NULL,
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
        $url;

        return new RequestDto('', new Uri($url));
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
        $id;

        return new RequestDto('', new Uri(''));
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


    /**
     * @return FormStack
     */
    public function getFormStack(): FormStack
    {
        $field1 = new Field(Field::TEXT, 'settings1', 'Client 11');
        $field2 = new Field(Field::TEXT, 'settings2', 'Client 22');
        $field3 = new Field(Field::PASSWORD, 'settings3', 'Client 33');

        $form = new Form(ApplicationInterface::AUTHORIZATION_FORM, 'Authorization settings');
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

        $formStack = new FormStack();
        $formStack->addForm($form);

        return $formStack;
    }

    /**
     * @return string
     */
    public function getFrontendRedirectUrl(): string
    {
        return 'http://example.com';
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return NullApplication
     */
    public function setFrontendRedirectUrl(ApplicationInstall $applicationInstall): NullApplication
    {
        $applicationInstall;

        return $this;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param mixed[]            $token
     *
     * @return NullApplication
     */
    public function setAuthorizationToken(ApplicationInstall $applicationInstall, array $token): NullApplication
    {

        $applicationInstall;
        $token;

        return $this;
    }

    /**
     *
     */
    public function authorize(): void
    {
        // Not Needed
    }

}
