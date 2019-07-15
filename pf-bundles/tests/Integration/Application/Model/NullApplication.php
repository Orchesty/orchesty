<?php declare(strict_types=1);

namespace Tests\Integration\Application\Model;

use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Enum\AuthorizationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\PipesFramework\Application\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Authorization\Base\Basic\BasicApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Authorization\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Authorization\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Authorization\Model\Form\Form;

/**
 * Class NullApplication
 *
 * @package Tests\Integration\Application\Model
 */
class NullApplication extends BasicApplicationAbstract implements WebhookApplicationInterface
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
        string $method,
        ?string $url,
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

    /**
     * @return Form
     * @throws ApplicationInstallException
     */
    public function getSettingsForm(): Form
    {

        $field1 = new Field(
            Field::TEXT,
            'settings1',
            'Client 11',
            );

        $field2 = new Field(
            Field::TEXT,
            'settings2',
            'Client 22'
        );

        $field3 = new Field(
            Field::PASSWORD,
            'settings3',
            'Client 33'
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

        return $form;
    }

}