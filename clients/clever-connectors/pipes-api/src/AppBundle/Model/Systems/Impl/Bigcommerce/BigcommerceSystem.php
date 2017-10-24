<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Exceptions\CleverConnectorsException;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;
use Nette\Utils\Json;

/**
 * Class BigcommerceSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce
 */
class BigcommerceSystem implements WebhookSystemInterface, AuthorizationInterface
{

    private const SYSTEM_URL   = 'https://api.bigcommerce.com/stores/%s/v2/';
    private const STORE_ID     = 'store_id';
    private const CLIENT_ID    = 'client_id';
    private const ACCESS_TOKEN = 'access_token';

    private const WEBHOOK_SUBSCRIBE_URL   = 'https://api.bigcommerce.com/stores/%s/v2/hooks';
    private const WEBHOOK_UNSUBSCRIBE_URL = 'https://api.bigcommerce.com/stores/%s/v2/hooks/%s';

    use AuthorizationTrait;
    use WebhookSystemTrait;

    /**
     * @var array
     */
    private $topics = [
        'bigcommerce-create-customer-connector' => 'store/customer/created',
        'bigcommerce-update-customer-connector' => 'store/customer/updated',
        'bigcommerce-delete-customer-connector' => 'store/customer/deleted',
    ];

    /**
     * BigcommerceSystem constructor.
     */
    public function __construct()
    {
        $this->subscriptions[] = new WebhookSubscribes(
            'bigcommerce-create-customer-connector',
            'bigcommerce-create-customer',
            self::WEBHOOK_SUBSCRIBE_URL,
            self::WEBHOOK_UNSUBSCRIBE_URL
        );

        $this->subscriptions[] = new WebhookSubscribes(
            'bigcommerce-update-customer-connector',
            'bigcommerce-update-customer',
            self::WEBHOOK_SUBSCRIBE_URL,
            self::WEBHOOK_UNSUBSCRIBE_URL
        );

        $this->subscriptions[] = new WebhookSubscribes(
            'bigcommerce-delete-customer-connector',
            'bigcommerce-delete-customer',
            self::WEBHOOK_SUBSCRIBE_URL,
            self::WEBHOOK_UNSUBSCRIBE_URL
        );
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $settings = $systemInstall->getSettings();

        return !empty($settings[self::STORE_ID] ?? '')
            && !empty($settings[self::CLIENT_ID] ?? '')
            && !empty($settings[self::ACCESS_TOKEN] ?? '');
    }

    /**
     * @return string
     */
    public function getAuthorizationType(): string
    {
        return self::BASIC;
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
        return 'bigcommerce';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Bigcommerce System';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Bigcommerce description...';
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
     * @param string        $method
     *
     * @return RequestDto
     * @throws SystemException
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        $this->continueOnAuthorized($systemInstall);

        return (new RequestDto($method, new Uri(sprintf(
            self::SYSTEM_URL, $systemInstall->getSettings()[self::STORE_ID]
        ))))->setHeaders($this->getHeaders($systemInstall));
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
            self::STORE_ID,
            'Store ID (XXX part in https://store-XXX.mybigcommerce.com)',
            $this->prepareValue(self::STORE_ID, $settings),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            self::CLIENT_ID,
            'Client ID',
            $this->prepareValue(self::CLIENT_ID, $settings),
            TRUE
        );

        $field3 = new Field(
            Field::TEXT,
            self::ACCESS_TOKEN,
            'Access Token',
            $this->prepareValue(self::ACCESS_TOKEN, $settings),
            TRUE
        );

        return (new Form())
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->toArray();
    }

    /**
     * @param WebhookSubscribes $subscription
     * @param SystemInstall     $systemInstall
     * @param string            $url
     *
     * @return RequestDto
     */
    public function getSubscribeRequest(
        WebhookSubscribes $subscription,
        SystemInstall $systemInstall,
        string $url
    ): RequestDto
    {
        return (new RequestDto(
            'POST',
            new Uri(sprintf($subscription->getSubscribeUrl(), $systemInstall->getSettings()[self::STORE_ID]))
        ))->setBody(Json::encode([
            'scope'       => $this->topics[$subscription->getNodeName()],
            'destination' => $url,
        ]))->setHeaders($this->getHeaders($systemInstall));
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $webhookId
     *
     * @return RequestDto
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto
    {
        return (new RequestDto(
            'DELETE',
            new Uri(sprintf(
                $this->subscriptions[0]->getUnSubscribeUrl(),
                $systemInstall->getSettings()[self::STORE_ID],
                $webhookId
            ))
        ))->setHeaders($this->getHeaders($systemInstall));
    }

    /**
     * @param ResponseDto $response
     *
     * @return string
     * @throws CleverConnectorsException
     */
    public function getWebhookId(ResponseDto $response): string
    {
        $data = Json::decode($response->getBody(), TRUE);
        if (!$data || !array_key_exists('id', $data)) {
            throw new CleverConnectorsException(
                'Missing webhook id data in response.',
                CleverConnectorsException::MISSING_DATA
            );
        }

        return (string) $data['id'];
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return array
     */
    private function getHeaders(SystemInstall $systemInstall): array
    {
        $settings = $systemInstall->getSettings();

        return [
            'X-Auth-Client' => $settings[self::CLIENT_ID],
            'X-Auth-Token'  => $settings[self::ACCESS_TOKEN],
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ];
    }

}