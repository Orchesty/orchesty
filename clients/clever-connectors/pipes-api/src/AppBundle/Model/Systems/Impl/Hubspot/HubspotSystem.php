<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use DateTime;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class HubspotSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot
 */
class HubspotSystem implements WebhookSystemInterface, OAuth2Interface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;

    private const APP_ID                          = 'app_id';
    private const USER_ID                         = 4846078;
    private const HAPI_KEY                        = 'abab4202-0a4b-4099-8b61-fe325790d7cd';
    private const CLIENT_ID                       = '91caba3e-b12b-44d9-8e37-93e50918efa9';
    private const CLIENT_SECRET                   = '35b03ce5-1c1c-4292-a089-655692392b28';
    private const AUTHORIZE_URL                   = 'https://app.hubspot.com/oauth/authorize';
    private const BASE_URL                        = 'https://api.hubapi.com';
    private const TOKEN_URL                       = 'https://api.hubapi.com/oauth/v1/token';
    private const WEBHOOK_SUBSCRIPTION_CREATE_URL = 'https://api.hubapi.com/webhooks/v1/%s/subscriptions?hapikey=%s&userId=%s';
    private const WEBHOOK_SUBSCRIPTION_DELETE_URL = 'https://api.hubapi.com/webhooks/v1/%s/subscriptions/%s?hapikey=%s&userId=%s';

    private const SUBSCRIPTION_TYPE = 'subscriptionType';
    private const PROPERTY_NAME     = 'propertyName';

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var array
     */
    private $scopes = ['contacts'];

    /**
     * HubspotSystem constructor.
     *
     * @param OAuth2Provider $provider
     */
    public function __construct(OAuth2Provider $provider)
    {
        $this->provider = $provider;

        $this->subscriptions[] = $this->prepareWebhookSubscription('contact.creation');
        $this->subscriptions[] = $this->prepareWebhookSubscription('contact.deletion');
        $this->subscriptions[] = $this->prepareWebhookSubscription('contact.propertyChange', 'firstname');
        $this->subscriptions[] = $this->prepareWebhookSubscription('contact.propertyChange', 'lastname');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::WEBHOOK;
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
        return 'Hubspot system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $sett = $systemInstall->getSettings();

        return !empty($sett[OAuth2Provider::ACCESS_TOKEN] ?? '')
            && !empty($sett[self::APP_ID] ?? '');
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::OAUTH2;
    }

    /**
     * @param SystemInstall $systemInstall
     */
    public function authorize(SystemInstall $systemInstall): void
    {
        $dto = $this->getDto($systemInstall);

        $this->provider->authorize($dto, $this->scopes);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveToken(SystemInstall $systemInstall, array $data): SystemInstall
    {
        $arr     = $this->provider->getAccessToken($this->getDto($systemInstall), $data);
        $expires = (new DateTime())->setTimestamp(time() + $arr['expires_in']);

        $systemInstall->setExpires($expires);
        $this->setSettings($systemInstall, $arr);

        // TODO: set webhook url via api

        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemInstall
     * @throws SystemException
     */
    public function refreshToken(SystemInstall $systemInstall): SystemInstall
    {
        $this->continueOnAuthorized($systemInstall);

        $settings = $systemInstall->getSettings();
        $dto      = $this->getDto($systemInstall);
        $dto->setCustomAppDependencies($systemInstall->getUser(), $this->getKey());

        $this->provider->refreshAccessToken(
            $dto,
            [OAuth2Provider::REFRESH_TOKEN => $settings[OAuth2Provider::ACCESS_TOKEN]]
        );

        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $dto = new RequestDto($method, new Uri(self::BASE_URL));
        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $settings = $systemInstall->getSettings();

        $field1 = new Field(
            Field::TEXT,
            self::APP_ID,
            'Client\'s app\'s ID.',
            $this->prepareValue(self::APP_ID, $settings),
            TRUE
        );

        $form = (new Form())->addField($field1);

        return $form->toArray();
    }

    /**
     * @param WebhookSubscribes $subscription
     * @param SystemInstall     $systemInstall
     * @param string            $url
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getSubscribeRequest(
        WebhookSubscribes $subscription,
        SystemInstall $systemInstall,
        string $url
    ): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $sett = $systemInstall->getSettings();
        $url  = sprintf($subscription->getSubscribeUrl(), $sett[self::APP_ID], self::HAPI_KEY, self::USER_ID);
        $dto  = new RequestDto('POST', new Uri($url));

        $body = [
            'subscriptionDetails' => $subscription->getParams(),
            'enabled'             => TRUE,
        ];

        $dto->setBody(json_encode($body));
        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $webhookId
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $sett = $systemInstall->getSettings();
        $url  = sprintf(
            $this->subscriptions[0]->getUnSubscribeUrl(),
            $sett[self::APP_ID],
            $webhookId,
            self::HAPI_KEY,
            self::USER_ID
        );
        $dto  = new RequestDto('DELETE', new Uri($url));

        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param ResponseDto $response
     *
     * @return string
     * @throws CleverConnectorsException
     */
    public function getWebhookId(ResponseDto $response): string
    {
        $body = json_decode($response->getBody(), TRUE);
        if (!$body || !array_key_exists('id', $body)) {
            throw new CleverConnectorsException(
                'Missing webhook id data in response.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $body['id'];
    }

    /******************************************  HELPERS  ****************************************/

    /**
     *
     * @param string      $type
     * @param null|string $propertyName
     *
     * @return WebhookSubscribes
     */
    private function prepareWebhookSubscription(string $type, ?string $propertyName = NULL): WebhookSubscribes
    {
        $params = [self::SUBSCRIPTION_TYPE => $type];
        if ($propertyName) {
            $params[self::PROPERTY_NAME] = $propertyName;
        }

        return new WebhookSubscribes(
            sprintf('%s-update-contact-connector', $this->getKey()),
            sprintf('%s-update-contact', $this->getKey()),
            self::WEBHOOK_SUBSCRIPTION_CREATE_URL,
            self::WEBHOOK_SUBSCRIPTION_DELETE_URL,
            $params
        );
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall): array
    {
        return [
            'X-Hubspot-Access-Token' => $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN],
            'Content-Type'           => 'application/json',
        ];
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return OAuth2Dto
     */
    private function getDto(SystemInstall $systemInstall): OAuth2Dto
    {
        $dto = new OAuth2Dto(
            self::CLIENT_ID,
            self::CLIENT_SECRET,
            AuthorizationUtils::generateUrl(),
            self::AUTHORIZE_URL,
            self::TOKEN_URL
        );

        $dto->setCustomAppDependencies($systemInstall->getUser(), $this->getKey());

        return $dto;
    }

}