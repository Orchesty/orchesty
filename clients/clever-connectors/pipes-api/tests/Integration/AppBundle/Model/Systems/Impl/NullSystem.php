<?php declare(strict_types=1);

namespace Tests\Integration\AppBundle\Model\Systems\Impl;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\OAuth2Interface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Authorization\Provider\Dto\OAuth2Dto;
use Hanaboso\PipesFramework\Authorization\Provider\OAuth2Provider;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\ResponseDto;

/**
 * Class NullSystem
 *
 * @package Tests\Integration\AppBundle\Model\Systems\Impl
 */
class NullSystem implements WebhookSystemInterface, OAuth2Interface
{

    use AuthorizationTrait;
    use WebhookSystemTrait;

    private const URL             = 'field1';
    private const CONSUMER_KEY    = 'field2';
    private const CONSUMER_SECRET = 'field3';

    /**
     * @var OAuth2Provider
     */
    private $provider;

    /**
     * NullSystem constructor.
     *
     * @param OAuth2Provider $provider
     */
    function __construct(OAuth2Provider $provider)
    {
        $this->provider        = $provider;
        $this->subscriptions[] = new WebhookSubscribes('node', 'top', 'uriReg', 'uriUnreg');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return SystemTypeEnum::CRON;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return 'null.user.group';
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'NULL';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Only for testing purposes';
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
     * @param string        $webhookId
     *
     * @return RequestDto
     */
    public function getUnsubscribeRequest(SystemInstall $systemInstall, string $webhookId): RequestDto
    {
        return new RequestDto('POST', new Uri('uriUnsub'));
    }

    /**
     * @param ResponseDto $response
     *
     * @return string
     */
    public function getWebhookId(ResponseDto $response): string
    {
        return '9';
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
     * @return bool
     */
    public function isAuthorized(SystemInstall $systemInstall): bool
    {
        $settings = $systemInstall->getSettings();

        // TODO check EXPIRES ?

        return !empty($settings[OAuth2Provider::ACCESS_TOKEN] ?? '');
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
            Field::URL,
            self::URL,
            '',
            $this->prepareValue(self::URL, $settings),
            TRUE
        );

        $field2 = new Field(
            Field::TEXT,
            self::CONSUMER_KEY,
            '',
            $this->prepareValue(self::CONSUMER_KEY, $settings),
            TRUE
        );

        $field3 = new Field(
            Field::PASSWORD,
            self::CONSUMER_SECRET,
            '',
            $this->prepareValue(self::CONSUMER_SECRET, $settings),
            TRUE
        );

        $form = (new Form())
            ->addField($field1)
            ->addField($field2)
            ->addField($field3);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     */
    public function authorize(SystemInstall $systemInstall): void
    {
        // TODO: Implement authorize() method.

        $this->provider->authorize(new OAuth2Dto('', '', '', '', ''), []);
    }

    /**
     * @param SystemInstall $systemInstall
     * @param array         $data
     *
     * @return SystemInstall
     */
    public function saveToken(SystemInstall $systemInstall, array $data): SystemInstall
    {
        // TODO: Implement saveToken() method.

        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return SystemInstall
     */
    public function refreshToken(SystemInstall $systemInstall): SystemInstall
    {
        $datetime     = clone $systemInstall->getExpires();
        $newTimestamp = $datetime->getTimestamp() + 3600;

        $systemInstall->setExpires($datetime->setTimestamp($newTimestamp));

        return $systemInstall;
    }

    /**
     * @param SystemInstall $systemInstall
     * @param string        $method
     *
     * @return RequestDto
     */
    public function getRequestDto(SystemInstall $systemInstall, string $method): RequestDto
    {
        return new RequestDto('POST', new Uri(''));
    }

    /**
     * @param WebhookSubscribes $subscription
     * @param SystemInstall     $systemInstall
     * @param string            $url
     *
     * @return RequestDto
     */
    public function getSubscribeRequest(WebhookSubscribes $subscription, SystemInstall $systemInstall,
                                        string $url): RequestDto
    {
        return new RequestDto('POST', new Uri('uriSub'));
    }

}