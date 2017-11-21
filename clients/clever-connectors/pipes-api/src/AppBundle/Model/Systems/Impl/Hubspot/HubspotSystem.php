<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester\HubspotRequester;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester\HubspotSubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot\Requester\HubspotUnsubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use CleverConnectors\AppBundle\Utils\WebhookUtils;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Authorization\Utils\ScopeFormatter;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class HubspotSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Hubspot
 */
class HubspotSystem implements WebhookSystemInterface, OAuth2Interface, CMEventSystemInterface
{

    use SystemTrait;
    use AuthorizationTrait;
    use WebhookSystemTrait;
    use CMEventSystemTrait;

    public const APP_ID   = 'app_id';
    public const USER_ID  = 4846078;
    public const HAPI_KEY = 'abab4202-0a4b-4099-8b61-fe325790d7cd';

    private const WEBHOOK_URL       = 'webhook_url';
    private const CLIENT_ID         = '91caba3e-b12b-44d9-8e37-93e50918efa9';
    private const CLIENT_SECRET     = '35b03ce5-1c1c-4292-a089-655692392b28';
    private const AUTHORIZE_URL     = 'https://app.hubspot.com/oauth/authorize';
    private const BASE_URL          = 'https://api.hubapi.com';
    private const TOKEN_URL         = 'https://api.hubapi.com/oauth/v1/token';
    private const CUSTOM_FIELDS_URL = 'https://api.hubapi.com/properties/v1/contacts/properties';

    public const OBJECT_ID_KEY         = 'objectId';
    public const SUBSCRIPTION_TYPE_KEY = 'subscriptionType';
    public const PROPERTY_NAME_KEY     = 'propertyName';

    public const SUBSCRIPTION_TYPE_CREATE = 'contact.creation';
    public const SUBSCRIPTION_TYPE_UPDATE = 'contact.propertyChange';
    public const SUBSCRIPTION_TYPE_DELETE = 'contact.deletion';

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var array
     */
    private $scopes = ['contacts'];

    /**
     * @var string
     */
    private $domain;

    /**
     * HubspotSystem constructor.
     *
     * @param OAuth2Provider $provider
     * @param string         $domain
     */
    public function __construct(OAuth2Provider $provider, string $domain)
    {
        $this->provider = $provider;
        $this->domain   = $domain;

        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE,
            SystemInstall::EVENT_UNSUBSCRIBE, self::CUSTOM_FIELDS_URL));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE,
            SystemInstall::EVENT_HARD_BOUNCE, self::CUSTOM_FIELDS_URL));

        $this->subscriptions[] = $this->prepareWebhookSubscription(self::SUBSCRIPTION_TYPE_CREATE);
        $this->subscriptions[] = $this->prepareWebhookSubscription(self::SUBSCRIPTION_TYPE_DELETE);
        $this->subscriptions[] = $this->prepareWebhookSubscription(self::SUBSCRIPTION_TYPE_UPDATE, 'firstname');
        $this->subscriptions[] = $this->prepareWebhookSubscription(self::SUBSCRIPTION_TYPE_UPDATE, 'lastname');

        $this->topologyNames['hubspot-hard-bounce-contact'] = 'hubspot-unsubscribe-contact';
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
        return 'Hubspot';
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

        $this->provider->authorize($dto, $this->scopes, ScopeFormatter::SPACE);
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
        $expires = (new DateTime())
            ->setTimestamp($arr['expires'])
            ->setTimezone(new DateTimeZone('UTC'));

        $systemInstall->setExpires($expires);
        $this->setSettings($systemInstall, $arr);

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

        $webhookUrl = WebhookUtils::getWebhookUrl(
            $this->domain,
            $systemInstall,
            $this->getNodeName(),
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey())
        );

        $field2 = new Field(
            Field::URL,
            self::WEBHOOK_URL,
            'Url',
            $webhookUrl
        );
        $field2
            ->setDescription('Url where client\'s app should send webhook messages to.')
            ->setRequired(TRUE);

        $field3 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'Unsubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field5 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard bounce events',
            $systemInstall->isEventHardBounce()
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4)
            ->addField($field5);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     * @throws SystemException
     */
    public function getSubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new HubspotSubscribeRequester($systemInstall, $this->getHeadersWithoutAuth());
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     * @throws SystemException
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new HubspotUnsubscribeRequester($systemInstall, $this->getHeadersWithoutAuth());
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|null
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface
    {
        return new HubspotRequester($this->getHeaders($systemInstall));
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
        $params = [self::SUBSCRIPTION_TYPE_KEY => $type];
        if ($propertyName) {
            $params[self::PROPERTY_NAME_KEY] = $propertyName;
        }

        return new WebhookSubscribes(
            $this->getNodeName(),
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey()),
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
            'Authorization' => 'Bearer ' . $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN],
            'Content-Type'  => 'application/json',
        ];
    }

    /**
     * @return array
     */
    private function getHeadersWithoutAuth(): array
    {
        return [
            'Content-Type' => 'application/json',
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

    /**
     * @return string
     */
    private function getNodeName(): string
    {
        return sprintf('%s-updated-contact-connector', $this->getKey());
    }

}