<?php declare(strict_types=1);

namespace Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot;

use Hanaboso\CommonsBundle\Enum\ApplicationTypeEnum;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\CurlManager;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\ResponseDto;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookApplicationInterface;
use Hanaboso\HbPFAppStore\Model\Webhook\WebhookSubscription;
use Hanaboso\PipesPhpSdk\Application\Base\ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Application\Document\ApplicationInstall;
use Hanaboso\PipesPhpSdk\Application\Exception\ApplicationInstallException;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Field;
use Hanaboso\PipesPhpSdk\Application\Model\Form\Form;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationAbstract;
use Hanaboso\PipesPhpSdk\Authorization\Base\OAuth2\OAuth2ApplicationInterface;
use Hanaboso\PipesPhpSdk\Authorization\Utils\ScopeFormatter;
use Hanaboso\Utils\String\Json;

/**
 * Class HubspotApplication
 *
 * @package Hanaboso\HbPFConnectors\Model\Application\Impl\Hubspot
 */
final class HubspotApplication extends OAuth2ApplicationAbstract implements WebhookApplicationInterface
{

    public const    BASE_URL    = 'https://api.hubapi.com';
    public const    HUBSPOT_URL = 'https://app.hubspot.com/oauth/authorize';
    public const    TOKEN_URL   = 'https://api.hubapi.com/oauth/v1/token';
    public const    APP_ID      = 'app_id';
    public const    HAPI_KEY    = 'hapi_key';
    public const    USER_ID     = 'user_id';

    protected const SCOPE_SEPARATOR = ScopeFormatter::SPACE;

    private const SCOPES = ['contacts'];

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
    public function getKey(): string
    {
        return 'hubspot';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Hubspot';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Hubspot v1';
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return self::HUBSPOT_URL;
    }

    /**
     * @return string
     */
    public function getTokenUrl(): string
    {
        return self::TOKEN_URL;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $method
     * @param string|null        $url
     * @param string|null        $data
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getRequestDto(
        ApplicationInstall $applicationInstall,
        string $method,
        ?string $url = NULL,
        ?string $data = NULL
    ): RequestDto
    {
        $request = new RequestDto($method, $this->getUri($url));
        $request->setHeaders(
            [
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getAccessToken($applicationInstall)),
            ]
        );

        if (isset($data)) {
            $request->setBody($data);
        }

        return $request;
    }

    /**
     * @return Form
     * @throws ApplicationInstallException
     */
    public function getSettingsForm(): Form
    {
        $form = new Form();
        $form
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_ID, 'Client Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, OAuth2ApplicationInterface::CLIENT_SECRET, 'Client Secret', TRUE))
            ->addField(new Field(Field::TEXT, self::APP_ID, 'Application Id', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::HAPI_KEY, 'Hapi Key', NULL, TRUE))
            ->addField(new Field(Field::TEXT, self::USER_ID, 'User Id', NULL, TRUE));

        return $form;
    }

    /**
     * @return WebhookSubscription[]
     */
    public function getWebhookSubscriptions(): array
    {
        return [
            new WebhookSubscription('Create Contact', 'starting-point', '', ['name' => 'contact.creation']),
            new WebhookSubscription('Delete Contact', 'starting-point', '', ['name' => 'contact.deletion']),
        ];
    }

    /**
     * @param ApplicationInstall  $applicationInstall
     * @param WebhookSubscription $subscription
     * @param string              $url
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getWebhookSubscribeRequestDto(
        ApplicationInstall $applicationInstall,
        WebhookSubscription $subscription,
        string $url
    ): RequestDto
    {

        $url;
        $request = new RequestDto(
            CurlManager::METHOD_POST,
            $this->getUri(
                sprintf(
                    '%s/webhooks/v1/%s/subscriptions?hapikey=%s&userId=%s',
                    self::BASE_URL,
                    $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::APP_ID],
                    $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::HAPI_KEY],
                    $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::USER_ID]
                )
            ),
            ['Content-Type' => 'application/json']
        );

        $request->setBody(
            Json::encode(
                [
                    'subscriptionDetails' => [
                        'subscriptionType' => $subscription->getParameters()['name'],
                        'propertyName'     => 'email',
                    ],
                    'enabled'             => FALSE,
                ]
            )
        );

        return $request;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     * @param string             $id
     *
     * @return RequestDto
     * @throws ApplicationInstallException
     * @throws CurlException
     */
    public function getWebhookUnsubscribeRequestDto(ApplicationInstall $applicationInstall, string $id): RequestDto
    {
        return new RequestDto(
            CurlManager::METHOD_DELETE,
            $this->getUri(
                sprintf(
                    '%s/webhooks/v1/%s/subscriptions/%s?hapikey=%s&userId=%s',
                    self::BASE_URL,
                    $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::APP_ID],
                    $id,
                    $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::HAPI_KEY],
                    $applicationInstall->getSettings()[ApplicationAbstract::FORM][self::USER_ID]
                )
            ),
            ['Content-Type' => 'application/json']
        );
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

        return (string) Json::decode($dto->getBody())['id'];
    }

    /**
     * @param ResponseDto $dto
     *
     * @return bool
     */
    public function processWebhookUnsubscribeResponse(ResponseDto $dto): bool
    {
        $dto;

        return $dto->getStatusCode() === 204;
    }

    /**
     * @param ApplicationInstall $applicationInstall
     *
     * @return string[]
     */
    protected function getScopes(ApplicationInstall $applicationInstall): array
    {
        $applicationInstall;

        return self::SCOPES;
    }

}
