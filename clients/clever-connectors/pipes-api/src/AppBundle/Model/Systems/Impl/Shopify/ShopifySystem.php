<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

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
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class ShopifySystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Shopify
 */
class ShopifySystem implements WebhookSystemInterface, OAuth2Interface
{

    private const SYSTEM_URL = 'system_url';

    private const API_KEY    = '91f0d11786afbe82fc72d519356bc7f2';
    private const API_SECRET = '469a399914df80fab1e223b18d9d95bc';

    private const WEBHOOK_SUBSCRIBE_URL   = 'https://%s.myshopify.com/admin/webhooks.json';
    private const WEBHOOK_UNSUBSCRIBE_URL = 'https://%s.myshopify.com/admin/webhooks/%s.json';

    use AuthorizationTrait;
    use WebhookSystemTrait;

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * @var array
     */
    private $topics = [
        'shopify-customer-create' => 'customers/create',
        'shopify-customer-update' => 'customers/update',
        'shopify-customer-delete' => 'customers/delete',
    ];

    /**
     * @var array
     */
    private $scopes = [
        'read_customers, write_customers',
    ];

    /**
     * ShopifySystem constructor.
     *
     * @param OAuth2Provider $provider
     */
    function __construct(OAuth2Provider $provider)
    {
        $this->provider = $provider;

        $this->subscriptions[] = new WebhookSubscribes('shopify-customer-create', 'shopify_create_subscriber_topology',
            self::WEBHOOK_SUBSCRIBE_URL, self::WEBHOOK_UNSUBSCRIBE_URL);

        $this->subscriptions[] = new WebhookSubscribes('shopify-customer-update', 'shopify_update_subscriber_topology',
            self::WEBHOOK_SUBSCRIBE_URL, self::WEBHOOK_UNSUBSCRIBE_URL);

        $this->subscriptions[] = new WebhookSubscribes('shopify-customer-delete', 'shopify_delete_subscriber_topology',
            self::WEBHOOK_SUBSCRIBE_URL, self::WEBHOOK_UNSUBSCRIBE_URL);

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
        return 'shopify';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Shopify';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Shopify system';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
    }

    /**
     * @param WebhookSubscribes $subs
     * @param SystemInstall     $systemInstall
     * @param string            $url
     *
     * @return RequestDto
     */
    public function getSubscribeRequest(WebhookSubscribes $subs, SystemInstall $systemInstall, string $url): RequestDto
    {
        $sett = $systemInstall->getSettings();

        $systemUrl = $sett[self::SYSTEM_URL];
        $topic     = $this->topics[$subs->getNodeName()];

        $dto = new RequestDto('POST',
            new Uri(sprintf($subs->getRegistrationUrl(), $systemUrl)));

        $dto->setBody(json_encode([
            'webhook' => [
                'topic'   => $topic,
                'address' => $url,
                'format'  => 'json',
            ],
        ]));

        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $webhookId
     *
     * @return RequestDto
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto
    {
        $sett = $systemInstall->getSettings();

        $dto = new RequestDto('DELETE',
            new Uri(sprintf($this->subscriptions[0]->getUnregistrationUrl(),
                $sett[self::SYSTEM_URL], $webhookId)));

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
        if (!$body || !array_key_exists('webhook', $body) || !array_key_exists('id', $body['webhook'])) {
            throw new CleverConnectorsException(
                'Missing webhook id data in response.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $body['webhook']['id'];
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
            && !empty($sett[self::SYSTEM_URL] ?? '');
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
     *
     * @return array
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $settings = $systemInstall->getSettings();

        $field1 = new Field(
            Field::TEXT,
            self::SYSTEM_URL,
            'System url of client\'s app - XXX.myshopify.com (only XXX part).',
            $this->prepareValue(self::SYSTEM_URL, $settings),
            TRUE
        );

        $form = (new Form())
            ->addField($field1);

        return $form->toArray();
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
        $arr = $this->provider->getAccessToken($this->getDto($systemInstall), $data);

        $systemInstall->setExpires(NULL);
        $this->setSettings($systemInstall, $arr);

        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemInstall
     */
    public function refreshToken(SystemInstall $systemInstall): SystemInstall
    {
        $settings = $systemInstall->getSettings();

        $dto = $this->getDto($systemInstall);

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
        if (!$this->isAuthorized($systemInstall)) {
            throw new SystemException('Shopify is not Authorized!', SystemException::SYSTEM_IS_UNAUTHORIZED);
        }

        $sett = $systemInstall->getSettings();

        $url = sprintf('https://%s.myshopify.com/', $sett[self::SYSTEM_URL]);
        $dto = new RequestDto($method, new Uri($url));
        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall): array
    {
        return [
            'X-Shopify-Access-Token' => $systemInstall->getSettings()[OAuth2Provider::ACCESS_TOKEN],
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
        $sett = $systemInstall->getSettings();

        $dto = new OAuth2Dto(
            self::API_KEY,
            self::API_SECRET,
            AuthorizationUtils::generateUrl(),
            sprintf('https://%s.myshopify.com/admin/oauth/authorize', $sett[self::SYSTEM_URL]),
            sprintf('https://%s.myshopify.com/admin/oauth/access_token', $sett[self::SYSTEM_URL])
        );

        $dto->setCustomAppDependencies($systemInstall->getUser(), $this->getKey());

        return $dto;
    }

}