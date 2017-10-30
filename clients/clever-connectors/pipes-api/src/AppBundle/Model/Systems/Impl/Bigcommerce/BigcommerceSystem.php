<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Requester\BigcommerceSubscribeRequester;
use CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce\Requester\BigcommerceUnsubscribeRequester;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class BigcommerceSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Bigcommerce
 */
class BigcommerceSystem implements WebhookSystemInterface, AuthorizationInterface
{

    public const  STORE_ID     = 'store_id';
    private const SYSTEM_URL   = 'https://api.bigcommerce.com/stores/%s/v2/';
    private const CLIENT_ID    = 'client_id';
    private const ACCESS_TOKEN = 'access_token';

    use AuthorizationTrait;
    use WebhookSystemTrait;

    /**
     * BigcommerceSystem constructor.
     */
    public function __construct()
    {
        $this->subscriptions[] = new WebhookSubscribes(
            'bigcommerce-create-customer-connector',
            'bigcommerce-create-customer'
        );
        $this->subscriptions[] = new WebhookSubscribes(
            'bigcommerce-update-customer-connector',
            'bigcommerce-update-customer'
        );
        $this->subscriptions[] = new WebhookSubscribes(
            'bigcommerce-delete-customer-connector',
            'bigcommerce-delete-customer'
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
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     */
    public function getSubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new BigcommerceSubscribeRequester($systemInstall, $this->getHeaders($systemInstall));
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        $this->continueOnAuthorized($systemInstall);

        return new BigcommerceUnsubscribeRequester($systemInstall, $this->getHeaders($systemInstall));
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
     * ----------------------------------- HELPERS ------------------------------------
     */

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