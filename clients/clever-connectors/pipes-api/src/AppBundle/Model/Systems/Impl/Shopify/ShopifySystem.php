<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Shopify;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Enum\SystemUITypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Limits\SystemLimitDto;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Requester\ShopifySubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Impl\Shopify\Requester\ShopifyUnsubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Traits\SystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\AuthorizationUtils;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\CommonsBundle\Transport\Curl\CurlException;
use Hanaboso\CommonsBundle\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;

/**
 * Class ShopifySystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Shopify
 */
class ShopifySystem implements WebhookSystemInterface, OAuth2Interface, CMEventSystemInterface
{

    use SystemTrait;
    use AuthorizationTrait;
    use WebhookSystemTrait;
    use CMEventSystemTrait;

    public const SYSTEM_URL = 'system_url';

    private const API_KEY    = '91f0d11786afbe82fc72d519356bc7f2';
    private const API_SECRET = '469a399914df80fab1e223b18d9d95bc';

    private const BASE_URL = 'https://%s.myshopify.com/';

    /**
     * @var OAuth2Provider
     */
    private $provider;

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
     *
     * @throws CleverConnectorsException
     */
    function __construct(OAuth2Provider $provider)
    {
        $this->provider = $provider;

        $this->subscriptions[] = new WebhookSubscribes(
            'shopify-created-customer-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::CREATED_SUBSCRIBERS, $this->getKey())
        );
        $this->subscriptions[] = new WebhookSubscribes(
            'shopify-updated-customer-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey())
        );
        $this->subscriptions[] = new WebhookSubscribes(
            'shopify-deleted-customer-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::DELETED_SUBSCRIBERS, $this->getKey())
        );

        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));

        $this->topologyNames[TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT,
            $this->getKey())] = TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT,
            $this->getKey());
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
    public function getUIType(): string
    {
        return SystemUITypeEnum::BASIC;
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
        return 'Shopify';
    }

    /**
     * @return string
     */
    public function getLogo(): string
    {
        return 'Logo';
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
     * @return RequesterInterface
     * @throws SystemException
     */
    public function getSubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new ShopifySubscribeRequester($systemInstall, $this->getHeaders($systemInstall));
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

        return new ShopifyUnsubscribeRequester($systemInstall, $this->getHeaders($systemInstall));
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
     * @param SystemInstall $systemInstall
     *
     * @return array
     * @throws CleverConnectorsException
     */
    public function getSettingFields(SystemInstall $systemInstall): array
    {
        $settings = $systemInstall->getSettings();

        $field1 = (new Field(
            Field::TEXT,
            self::SYSTEM_URL,
            'Store ID',
            $this->prepareValue(self::SYSTEM_URL, $settings),
            TRUE
        ))->setDescription('Store ID (XXX part in https://XXX.myshopify.com)');

        $field2 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field3 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'Unsubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard bounce events',
            $systemInstall->isEventHardBounce()
        );

        $field5 = new Field(
            Field::SELECT,
            SystemInstall::SELECT_LIST,
            'Distribution list',
            $this->prepareValue(SystemInstall::SELECT_LIST, $settings)
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
     * @throws CurlException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        $sett = $systemInstall->getSettings();

        $url = sprintf(self::BASE_URL, $sett[self::SYSTEM_URL]);
        $dto = new RequestDto($method, new Uri($url));
        $dto->setHeaders($this->getHeaders($systemInstall));

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemLimitDto|null
     */
    public function getLimit(SystemInstall $systemInstall): ?SystemLimitDto
    {
        return new SystemLimitDto(
            $systemInstall,
            SystemLimitDto::LIMIT_FOR_USER,
            20,
            40
        );
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveLimit(SystemInstall $systemInstall, array $data): SystemInstall
    {
        return $systemInstall;
    }

    /**
     * -------------------------------------- HELPERS -----------------------------------
     */

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
            sprintf(self::BASE_URL . 'admin/oauth/authorize', $sett[self::SYSTEM_URL]),
            sprintf(self::BASE_URL . 'admin/oauth/access_token', $sett[self::SYSTEM_URL])
        );

        $dto->setCustomAppDependencies($systemInstall->getUser(), $this->getKey());

        return $dto;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|null
     */
    public function getCMEventRequester(SystemInstall $systemInstall): ?RequesterInterface
    {
        return NULL;
    }

}