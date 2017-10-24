<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\WebhookUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class NutshellSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell
 */
class NutshellSystem implements AuthorizationInterface, WebhookSystemInterface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;

    private const SYSTEM_URL = 'https://app.nutshell.com/api/v1/json';
    private const USERNAME   = 'username';
    private const API_KEY    = 'api_key';

    /**
     * @var string
     */
    private $url;

    /**
     * NutshellSystem constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->url             = $url;
        $this->subscriptions[] = new WebhookSubscribes(
            'nutshell-contact-connector',
            'nutshell-contact',
            '',
            ''
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

        return !empty($settings[self::USERNAME] ?? '') && !empty($settings[self::API_KEY] ?? '');
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
        return SystemTypeEnum::UI_WEBHOOK;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'nutshell';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Nutshell system';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Nutshell description...';
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
        if (!$this->isAuthorized($systemInstall)) {
            throw new SystemException('Nutshell is not Authorized!', SystemException::SYSTEM_IS_UNAUTHORIZED);
        }

        $settings      = $systemInstall->getSettings();
        $authorization = sprintf('%s:%s', $settings[self::USERNAME], $settings[self::API_KEY]);

        return (new RequestDto($method, new Uri(self::SYSTEM_URL)))
            ->setHeaders([
                'Authorization' => sprintf('Basic %s', base64_encode($authorization)),
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]);
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
            self::USERNAME,
            'Username',
            $this->prepareValue(self::USERNAME, $settings),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            self::API_KEY,
            'API Key',
            $this->prepareValue(self::API_KEY, $settings),
            TRUE
        );

        $field3 = new Field(
            Field::URL,
            'webhook_url',
            'Webhook url',
            WebhookUtils::getWebhookUrl(
                $this->url,
                $systemInstall->getUser(),
                $systemInstall->getToken(),
                'nutshell-customer-connector',
                'nutshell-customer'
            ),
            FALSE,
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
        return new RequestDto('', new Uri());
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $webhookId
     *
     * @return RequestDto
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto
    {
        return new RequestDto('', new Uri());
    }

    /**
     * @param ResponseDto $response
     *
     * @return string
     */
    public function getWebhookId(ResponseDto $response): string
    {
        return '';
    }

}