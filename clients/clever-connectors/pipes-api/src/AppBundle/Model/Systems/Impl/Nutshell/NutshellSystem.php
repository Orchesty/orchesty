<?php declare(strict_types=1);

namespace CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell;

use CleverConnectors\AppBundle\Document\SystemInstall;
use CleverConnectors\AppBundle\Enum\CleverCustomKeysEnum;
use CleverConnectors\AppBundle\Enum\SystemTypeEnum;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventObject;
use CleverConnectors\AppBundle\Model\CMEvents\CMEventSystemInterface;
use CleverConnectors\AppBundle\Model\CMEvents\Traits\CMEventSystemTrait;
use CleverConnectors\AppBundle\Model\Form\Field;
use CleverConnectors\AppBundle\Model\Form\Form;
use CleverConnectors\AppBundle\Model\Requester\RequesterInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\AuthorizationInterface;
use CleverConnectors\AppBundle\Model\Systems\Authorizations\Traits\AuthorizationTrait;
use CleverConnectors\AppBundle\Model\Systems\Exceptions\SystemException;
use CleverConnectors\AppBundle\Model\Webhook\Traits\WebhookSystemTrait;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSubscribes;
use CleverConnectors\AppBundle\Model\Webhook\WebhookSystemInterface;
use CleverConnectors\AppBundle\Utils\TopologyNameUtils;
use CleverConnectors\AppBundle\Utils\WebhookUtils;
use GuzzleHttp\Psr7\Uri;
use Hanaboso\PipesFramework\Commons\Transport\Curl\Dto\RequestDto;

/**
 * Class NutshellSystem
 *
 * @package CleverConnectors\AppBundle\Model\Systems\Impl\Nutshell
 */
class NutshellSystem implements AuthorizationInterface, CMEventSystemInterface, WebhookSystemInterface
{

    use AuthorizationTrait;
    use CMEventSystemTrait;
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
            'nutshell-updated-contact-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey())
        );

        $this->addCMEvent(new CMEventObject('', SystemInstall::EVENT_CREATE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::UNSUBSCRIBE, SystemInstall::EVENT_UNSUBSCRIBE, ''));
        $this->addCMEvent(new CMEventObject(CleverCustomKeysEnum::HARD_BOUNCE, SystemInstall::EVENT_HARD_BOUNCE, ''));

        $unSubscribeKey = TopologyNameUtils::getTopologyName(TopologyNameUtils::UNSUBSCRIBE_CONTACT, $this->getKey());
        $hardBounceKey  = TopologyNameUtils::getTopologyName(TopologyNameUtils::HARD_BOUNCE_CONTACT, $this->getKey());

        $this->topologyNames[$unSubscribeKey] = 'nutshell-update-contact';
        $this->topologyNames[$hardBounceKey]  = 'nutshell-update-contact';
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
        return 'Nutshell';
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
        $this->continueOnAuthorized($systemInstall);

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

        $webhookUrl = WebhookUtils::getWebhookUrl(
            $this->url,
            $systemInstall,
            'nutshell-updated-contact-connector',
            TopologyNameUtils::getTopologyName(TopologyNameUtils::UPDATED_SUBSCRIBERS, $this->getKey())
        );

        $field3 = new Field(
            Field::URL,
            'webhook_url',
            'Webhook url',
            $webhookUrl
        );
        $field3->setReadOnly(TRUE);

        $field4 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_CREATE,
            'Create event',
            $systemInstall->isEventCreate()
        );

        $field5 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_UNSUBSCRIBE,
            'UnSubscribe event',
            $systemInstall->isEventUnsubscribe()
        );

        $field6 = new Field(
            Field::CHECKBOX,
            SystemInstall::EVENT_HARD_BOUNCE,
            'Hard Bounce event',
            $systemInstall->isEventHardBounce()
        );

        $form = new Form();
        $form
            ->addField($field1)
            ->addField($field2)
            ->addField($field3)
            ->addField($field4)
            ->addField($field5)
            ->addField($field6);

        return $form->toArray();
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|void
     * @throws SystemException
     */
    public function getSubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        throw new SystemException(
            'Method [getSubscribeRequester] not implemented in Nutshell system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND
        );
    }

    /**
     * @param SystemInstall $systemInstall
     *
     * @return RequesterInterface|void
     * @throws SystemException
     */
    public function getUnsubscribeRequester(SystemInstall $systemInstall): RequesterInterface
    {
        throw new SystemException(
            'Method [getUnsubscribeRequester] not implemented in Nutshell system.',
            SystemException::SYSTEM_METHOD_NOT_FOUND
        );
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